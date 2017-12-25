<?php
/**
 * Created by PhpStorm.
 * User: ovo
 * Date: 2017/7/10
 * Time: 下午6:08
 */
namespace app\backsystem\controller;

use app\backsystem\model\AccountModel;
use app\backsystem\model\WithdrawModel;
use app\backsystem\model\UserModel;
use Service\Wechat;
use Service\Alipay;
use think\Db;
use think\Exception;
use think\Request;

class Withdraw extends Base{
    const WITHDRAW = 'withdraw';//提现表
    const ACCOUNT = 'account';//账户明细表
    const USER = 'users';        //用户表
    //用户列表
    public function index(){
        $user_class = config('user_class');
        if(request()->isAjax()){
            $user = new UserModel();
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = $whereu = $uids = [];
            if (isset($param['truename']) && !empty($param['truename'])) {
                $whereu['nickname'] = ['like', '%' . $param['truename'] . '%'];
            }
            if (isset($param['phone']) && !empty($param['phone'])) {
                $whereu['phone'] = ['like', '%' . $param['phone'] . '%'];
            }
            if(!empty($whereu)){
                $uids = $user->where($whereu)->column('id');
            }

            if (isset($param['status']) && !empty($param['status']) && $param['status']!="未选择" || (int)$param['status'] == 0) {
                $where['status'] = ['=',$param['status']];
            }
            if($param['status']=="未选择"){
                unset($where['status']);
            }
            if (isset($param['end']) && !empty($param['end']) && isset($param['start']) && !empty($param['start'])) {
                $time[1] = $param['end'].' 23:59:59';
                $time[0] = $param['start'].' 00:00:00';
                $where['created_at'] = ['between',$time];
            }
            $withdraw = new WithdrawModel();
            if(isset($param['excel']) && $param['excel'] == 'to_excel'){
                $offset = 0;
                $limit = 9999;
            }
            $selectResult = $withdraw->getWithdrawByWhere($where, $offset, $limit,$uids);
            $status = config('Withdraw_status');
            $type = config('Withdraw_type');
            // $config = unserialize(file_get_contents('./config'));
            foreach($selectResult as $key=>$vo){
                $user = db(self::USER)->where(['id'=>$vo['uid']])->find();
                $selectResult[$key]['phone'] = $user['phone'];
                $selectResult[$key]['nickname'] = $user['nickname'];
                $selectResult[$key]['status'] = $status[$vo['status']];
                if($param['excel'] != 'to_excel') {
                    $selectResult[$key]['type'] = '<a href="javascript:getOrder(' . $vo['id'] . ')">' . $type[$vo['type']] . '</a>';
                }
                if($vo['status'] == '申请中'){
                    $operate = [
                        '同意' => "javascript:grant('".$vo['id']."')",
                        '拒绝' => "javascript:down('".$vo['id']."')"
                    ];
                    $selectResult[$key]['operate'] = showOperate($operate);
                }else{
                    $selectResult[$key]['operate'] = '-';
                }

            }
            if(isset($param['excel']) && $param['excel'] == 'to_excel'){    //导出到excel
                $content = $selectResult;
                $content = json_decode(json_encode($content),true);
                foreach($content as $k=>$v){
                    unset($content[$k]['operate']);
                    unset($content[$k]['uid']);
                    unset($content[$k]['updated_at']);
                    unset($content[$k]['s_msg']);
                    unset($content[$k]['user_name']);
                    unset($content[$k]['user_phone']);
                    unset($content[$k]['phone']);
                    unset($content[$k]['nickname']);
                    $content[$k]['type'] = $type[$v['type']];
                }
//                dump($content);die;
                $excel = new Excel();
                $first = ['A1'=>'编号ID','B1'=>'提现金额','C1'=>'提现手续费','D1'=>'到账金额','E1'=>'状态','F1'=>'提现申请时间','G1'=>'提现方式','H1'=>'收款码','I1'=>'开户行','J1'=>'银行卡号'];
                $excel->toExcel('提现列表',$content,$first);
                return json(['code'=>1]);
            }

            $return['total'] = $withdraw->getAllWithdraw($where,$uids);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }
        $this->assign('user_class',$user_class);
        return $this->fetch();
    }

     #拒绝提现
    public function down(){
      $id = input('param.id');
      $withdraw = new WithdrawModel();
      $data = $withdraw->getOneWithdraw($id);
      Db::startTrans();
        try{
            //返回用户余额
            db('users')->where(['id'=>$data['uid']])->setInc('balance',$data['money']);
            //增加余额记录
            $list = AccountModel::getAccountData($data['uid'],$data['money'],'提现驳回',5,1,'',$data['id']);
            AccountModel::create($list);
            //改变提现订单状态
            $withdraw->editWithdraw(['id'=>$id,'status'=>3]);
            Db::commit();
            return json(['code' => 1, 'data' => '', 'msg' => '已拒绝提现']);
        }catch(Exception $e){
            Db::rollback();
            return json(['code' => 10, 'data' => '', 'msg' =>$e->getMessage()]);
        }
    }
    #发放返现
    public function grant(){
        $id = input('param.id');
        $withdraw = new WithdrawModel();
        $data = $withdraw->getOneWithdraw($id);
        if($withdraw->where(['id'=>$id])->value('status')==1){
//            $result = self::cash($id,$data['type']);
//            if($result['status']==1){
                $flag = $withdraw->editWithdraw(['id'=>$id,'status'=>2]);
                return json($flag);
//            }else{
//                return json(['code'=>0,'data'=>'','msg'=>$result['data']]);
//            }
        }else{
            return json(['code' => 10, 'data' => '', 'msg' => '操作失败']);
        } 
    }
    #执行返现
   private function cash($id,$type){
        $withdraw = new WithdrawModel();
        $data = $withdraw->getOneWithdraw($id); 
        if($type==0){
            #微信返现(待定)

            return ['status'=>0,'data'=>'系统暂不满足微信提现条件，请换种方式提现'];


        }else if($type == 1){
            #支付宝返现
            $account = db('users')->where(['id'=>$data['uid']])->find();
            $osn = orderNum();
            $payee_account = $account['zhifubao'];
            $conf = unserialize(file_get_contents('./config'));
            $amount = $data['money']*(100-$conf['fee'])*0.01;
            // $amount = 0.01; 
            $content = [
                #单号
                'trans_no' => $osn,    
                'payee_type' => 'ALIPAY_LOGONID',
                #提现账号    
                'payee_account' => $payee_account,
                #提现金额    
                'amount' => $amount,
                #留言    
                'remark' => "金帮手的提现",
                #真实姓名    
                'payee_real_name' => $account['alname'],
                ];
            $msg = Alipay::querys($content);
            if (is_object($msg) && $msg->msg == 'Success') {
                return ['status'=>1,'data'=>"提现成功"];
            }else{
                return ['status'=>0,'data'=>$msg->msg];
            }
        }
   }


    public function getdetail(Request $request){
        $id = $request->param('id');
        $data = WithdrawModel::get($id);
        $return = [];
        $return['nickname'] = $data['users']['nickname'];
        $return['type'] =config('Withdraw_type')[$data['type']];
        $return['bank_name'] = $data['bank_name'];
        $return['bank_account'] = $data['bank_account'];
        $return['user_name'] = $data['user_name'];
        $return['user_phone'] = $data['user_phone'];
        $return['voucher'] = $data['voucher'];
        return json($return);
    }



}