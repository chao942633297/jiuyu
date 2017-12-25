<?php
/**
 * Created by PhpStorm.
 * User: ovo
 * Date: 2017/7/10
 * Time: 下午6:08
 */
namespace app\backsystem\controller;

use app\backsystem\model\OrderModel;
use app\backsystem\model\VoucherModel;
use app\backsystem\model\WithdrawModel;
use app\backsystem\model\UserModel;
use app\backsystem\model\RechargeModel;

class Statement extends Base{
    const USER = 'users';//用户表
    const ACCOUNT = 'account';//账户明细表
    const ORDER = 'order';//账户明细表
    const ADDRESS = 'address';
    const DETAIL = 'order_detail';
    const GOODS = 'goods';
    #微信充值订单
    public function recharge(){
        if(request()->isAjax()){
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = $whered = $uids = [];
            if (isset($param['name']) && !empty($param['name'])) {      //收货人查询
                $whered['nickname'] = ['like','%'.$param['name'].'%'];
            }
            if (isset($param['phone']) && !empty($param['phone'])) {      //手机号查询
                $whered['phone'] = ['like','%'.$param['phone'].'%'];
            }
            if($whered){
                $uids = db('users')->where($whered)->column('id');
            }

            //下单时间段
            if (isset($param['end']) && !empty($param['end']) && isset($param['start']) && !empty($param['start'])) {
                $time[1] = strtotime($param['end'].' 23:59:59');
                $time[0] = strtotime($param['start'].' 00:00:00');
                $where['created_at'] = ['between',$time];
            }

            $voucher = new VoucherModel();
            if(isset($param['excel']) && $param['excel'] == 'to_excel'){
                $offset = 0;
                $limit = 9999;
            }
            $selectResult = $voucher->all(function($query)use($where,$offset,$limit,$uids){
                $query->order('id','desc');
                $query->limit($offset,$limit);
                $query->where($where);
                if($uids){
                    $query->where('uid','in',$uids);
                }
            });
            foreach($selectResult as $key=>$vo){
                if($param['excel'] != 'to_excel'){
                    $selectResult[$key]['id'] = $vo['id'];
                }
                $selectResult[$key]['user_name'] = $vo['user']['nickname'];
                $selectResult[$key]['user_phone'] = $vo['user']['phone'];
//                $selectResult[$key]['voucher'] = '<img onclick="toBig(\''.$vo['img'].'\')" src="'.$vo['img'].'" style="width:50px;height:50px" />';
                $selectResult[$key]['voucher'] = '<div class="img'.$vo['id'].'" ><img layer-pid='.$vo['id'].' onclick="toBig('.$vo['id'].')" layer-src="'.$vo['img'].'" src="'.$vo['img'].'" style="height:100px;"  alt="'.$vo['user']['nickname'].'"></div>';
                $selectResult[$key]['export'] = $vo['img'];
                $selectResult[$key]['act_nickname'] = $vo['activation']['nickname'];
                $selectResult[$key]['act_phone'] = $vo['activation']['phone'];
                $selectResult[$key]['create_at'] = $vo['created_at'];
            }
            if(isset($param['excel']) && $param['excel'] == 'to_excel'){    //导出到excel
                $content = json_decode(json_encode($selectResult),true);
                foreach($content as $k=>$v){
                    unset($content[$k]['uid']);
                    unset($content[$k]['img']);
                    unset($content[$k]['voucher']);
                    unset($content[$k]['actid']);
                    unset($content[$k]['user']);
                    unset($content[$k]['activation']);
                    unset($content[$k]['created_at']);
                }
//                dump($content);exit;
                $excel = new Excel();
                $first = ['A1'=>'编号ID','B1'=>'用户名','C1'=>'手机号','D1'=>'支付凭证','E1'=>'激活人昵称','F1'=>'激活人手机号','G1'=>'下单时间'];
                $excel->toExcel('订单列表',$content,$first);
                return json(['code'=>1]);
            }
            $return['total'] = $voucher->getAllData($where,$uids);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }
}