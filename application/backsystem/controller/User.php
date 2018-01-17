<?php
/**
 * Created by PhpStorm.
 * User: ovo
 * Date: 2017/7/10
 * Time: 下午6:08
 */
namespace app\backsystem\controller;

use app\backsystem\model\AccountModel;
use app\backsystem\model\UserModel;
use app\backsystem\model\ApplyModel;
use app\home\controller\Rebate;
use think\Db;
use think\Exception;

class User extends Base{
    const USER = 'users';//用户表
    const ACCOUNT = 'account';//账户明细表
    //用户列表
    public function index()
    {
        $user_class = config('user_class');
        if(request()->isAjax()){
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['truename']) && !empty($param['truename'])) {
                $where['truename'] = ['like', '%' . $param['truename'] . '%'];
            }
            if (isset($param['nickname']) && !empty($param['nickname'])) {
                $where['nickname'] = ['like', '%' . $param['nickname'] . '%'];
            }
            if (isset($param['phone']) && !empty($param['phone'])) {
                $where['phone'] = ['like', '%' . $param['phone'] . '%'];
            }

            if (isset($param['user_class']) && !empty($param['user_class'])) {
                $where['class'] = $param['user_class'];
            }

            if (isset($param['p_phone']) && !empty($param['p_phone'])) {
                $p_u = db('users')->where(['phone'=>$param['p_phone']])->value('id');
                $where['pid'] = $p_u;
            }
            if (isset($param['end']) && !empty($param['end']) && isset($param['start']) && !empty($param['start'])) {
                $time[0] = $param['start'].' 00:00:00';
                $time[1] = $param['end'].' 23:59:59';
                $where['created_at'] = ['between',$time];
            }

            $user = new UserModel();
            $selectResult = $user->getUsersByWhere($where, $offset, $limit);
            $status = config('user_status');
            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['phone'] = '<a href="javascript:user_detail('.$vo['id'].')">'.$vo['nickname'].'('.$vo['phone'].')</a>';
                $p_user = db('users')->where(['id'=>$vo['pid']])->find();

//                $selectResult[$key]['rowNum'] = db('row')->where(['user_id'=>$vo['id'],'position'=>1])->count();
                if($p_user){
                    if($p_user['nickname']){
                        $selectResult[$key]['p_phone'] = '<a href="javascript:user_detail('.$p_user['id'].')">'.$p_user['nickname'].'('.$p_user['phone'].')</a>';
                    }else{
                        $selectResult[$key]['p_phone'] = '未定义昵称('.$p_user['phone'].')';
                    }
                }else{
                    $selectResult[$key]['p_phone'] = '平台';
                }
                $selectResult[$key]['status'] = $status[$vo['status']];
                $selectResult[$key]['user_class'] = $user_class[$vo['class']];

                $operate = [
                    '编辑' => url('user/userEdit', ['id' => $vo['id']]),
                    '充值用户余额' => "javascript:userEdit('".$vo['id']."','".$vo['balance']."')",
                    '解冻直推金额' => "javascript:thawBouns('".$vo['id']."','".$vo['direct_frozen']."')",
                    '用户详情' => "javascript:user_detail('".$vo['id']."')",
                    '资金明细' => "javascript:user_account('".$vo['id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);

            }

            $return['total'] = $user->getAllUsers($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        $this->assign('user_class',$user_class);
        return $this->fetch();
    }

    //编辑角色
    public function userEdit()
    {
        $user = new UserModel();
        $id = input('param.id');
        $userData = $user->getOneUser($id);
        $parent = db('users')->where(['id'=>$userData['pid']])->find();
        if(request()->isPost()){
            $param = input('post.');
            $param = parseParams($param['data']);

            if(empty($param['password'])){
                unset($param['password']);
            }else{
                $param['password'] = md5($param['password']);
            }
            if(!empty($param['p_phone'])){
                $p = db('users')->where(['phone'=>$param['p_phone']])->find();
                if($p){
                    $param['pid'] = $p['id'];
                }else{
                    return json(['code'=>1001,'msg'=>'上级手机号不存在']);
                }
            }
            unset($param['p_phone']);
            $flag = $user->editUser($param);
            //若修改用户上级, 则用户进入公排,
            if(isset($param['pid'])){
                $selfData = $user->getOneUser($param['id']);
                $rebate = new Rebate();
                $rebate->goQualifying($selfData['id'],$selfData['phone'],$param['pid']);
            }
            return json($flag);
        }

        if(request()->isGet() && !empty(input('money'))){
            $id = input('id');
            $money = input('money');
            $data = [
                'balance'=>['exp','balance + '.$money],
                'total_price'=>['exp','total_price + '.$money],
                'id'=>$id
            ];
            $result = db('users')->update($data);
//            $data = UserModel::get($id);
//            $data->total_price += $money;
//            $data->balance += $money;
//          //  $userData->frozen_price += $money;
//            $result = $data->save();
            if($result){
                $inc = 1;       //增加
                $msg = '后台充值';
                if($money < 0){
                    $inc = 2;    //减少
                    $msg = '后台扣除';
                }
                $insert = AccountModel::getAccountData($id,$money,$msg,6,$inc,'');
                db(self::ACCOUNT)->insert($insert);
                return json(['code' => 1, 'data' => '', 'msg' => '编辑成功']);
            }else{
                return json(['code' => 2, 'data' => '', 'msg' => '编辑失败']);
            }
        }

        $user_class = config('user_class');
        $this->assign([
            'user' => $userData,
            'user_class' => $user_class,
            'status' => config('user_status'),
            'parent'=>$parent
        ]);
        return $this->fetch();
    }



    //解冻直推冻结金额
    public function thawBouns(){
        $id = input('id');
        $money = input('money');
        if(empty($money)){
            return json(['msg'=>'解冻金额错误','code'=>2]);
        }
        $user = UserModel::get($id);
        if($money > $user['direct_frozen']){
            return json(['msg'=>'解冻金额超出冻结金额'.$id,'code'=>2]);
        }
        $data = [
            'balance'=>['exp','balance + '.$money],
            'total_price'=>['exp','total_price + '.$money],
            'direct_frozen'=>['exp','direct_frozen - '.$money],
            'id'=>$id
        ];
        $result = db('users')->update($data);
        if($result){
            return json(['msg'=>'解冻成功','code'=>1]);
        }
        return json(['msg'=>'解冻失败','code'=>2]);
    }






    //用户详情
    public function detail(){
        $id = input('id');
        $users = new UserModel();
        $user = $users->getOneUser($id);
        $user['card_name'] = $user['card']['card_name'];
        $user['card_num'] = $user['card']['card_number'];
        $user['user_address'] = $user['address']['province'].'/'.$user['address']['city'].'/'.$user['address']['area'];
        $return = [
            'user' => $user,
        ];
        return json($return);
    }

    //用户资金详情
    public function account(){
        $id = input('id');
        $user = db('account')->where(['uid'=>$id])->order('id desc')->select();
        foreach($user as $k=>$v){
            $user[$k]['create_at'] = date('Y-m-d H:i:s',$v['create_at']);
            $user[$k]['type'] = config('account_type')[$v['type']];
        }
        return json($user);
    }



    //报单中心申请
    public function apply(){
        if(request()->isAjax()){
            $user = new UserModel();
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = $whereu  = $uids = [];
            if (isset($param['truename']) && !empty($param['truename'])) {
                $whereu['nickname'] = ['like', '%' . $param['truename'] . '%'];
            }
            if (isset($param['phone']) && !empty($param['phone'])) {
                $whereu['phone'] = ['like', '%' . $param['phone'] . '%'];
            }
            if(!empty($whereu)){
                $uids = $user->where($whereu)->column('id');
            }

            //报单中心地址搜素
            if(isset($param['province']) && !empty($param['province'])){
                $where['province'] =['like', '%' . $param['province'] . '%'];
            }
            if(isset($param['city']) && !empty($param['city'])){
                $where['city'] =['like', '%' . $param['city'] . '%'];
            }
            if(isset($param['area']) && !empty($param['area'])){
                $where['area'] =['like', '%' . $param['area'] . '%'];
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
            $apply = new ApplyModel();
            if(isset($param['excel']) && $param['excel'] == 'to_excel'){
                $offset = 0;
                $limit = 9999;
            }
            $selectResult = $apply->all(function($query)use($where,$offset,$limit,$uids){
                $query->order('id','desc');
                $query->where($where);
                if($uids){
                    $query->where('uid','in',$uids);
                }
                $query->limit($offset,$limit);
            });
            $level = config('user_class');
            $status = config('Withdraw_status');
            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['user_name'] = $vo['user']['nickname'];
                $selectResult[$key]['user_phone'] = $vo['user']['phone'];
                $selectResult[$key]['level'] = $level[$vo['level']];
                $selectResult[$key]['status'] = $status[$vo['status']];

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
                }
                $excel = new Excel();
                $first = ['A1'=>'编号ID','B1'=>'提现金额','C1'=>'提现手续费','D1'=>'到账金额','E1'=>'状态','F1'=>'提现申请时间','G1'=>'提现方式','H1'=>'收款码','I1'=>'开户行','J1'=>'银行卡号'];
                $excel->toExcel('提现列表',$content,$first);
                return json(['code'=>1]);
            }

            $return['total'] = db('apply')->where($where)->count();  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }
        return $this->fetch();
    }


    //同意
    public function agree(){
        $id = input('id');
        $data = db('apply')->where('id',$id)->find();
        $where = [];



        $where = [];
        if ($data['level'] <= 5) {
            $where['province'] = $data['province'];
            $where['level'] = 5;
        }
        if ($data['level'] <= 4) {
            $where['city'] = $data['city'];
            $where['level'] = 4;
        }
        if ($data['level'] == 3) {
            $where['area'] = $data['area'];
            $where['level'] = 3;
        }
        $where['status'] =2;
        $count = db('apply')->where($where)->count();
        if($count >= 1){
            return json(['msg'=>'该地区已有报单中心','code'=>1001]);
        }
        Db::startTrans();
        try{
            //改变申请状态
            db('apply')->where('id',$id)->update(['status'=>2]);
            //改变用户级别
            db('users')->where('id',$data['uid'])->update(['class'=>$data['level']]);
            Db::commit();
            return json(['msg'=>'已同意','code'=>200]);
        }catch(Exception $e){
            Db::rollback();
            return json(['msg'=>$e->getMessage(),'code'=>1002]);
        }

    }


    //拒绝
    public function refuse(){
        $id = input('id');
        $res = db('apply')->where('id',$id)->update(['status'=>3]);
        if($res){
            return json(['msg'=>'已拒绝','code'=>1001]);
        }
        return json(['msg'=>'失败','code'=>200]);
    }










}