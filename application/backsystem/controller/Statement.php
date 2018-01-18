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

            if (isset($param['buyer_name']) && !empty($param['buyer_name'])) {      //收货人查询
                $where['consignee'] = $param['buyer_name'];
            }

            if (isset($param['buyer_phone']) && !empty($param['buyer_phone'])) {      //收货人手机查询
                $where['phone'] = $param['buyer_phone'];
            }

            if (isset($param['type']) && $param['type'] != '0') {      //套餐类型查询
                $where['type'] = $param['type'];
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
                $selectResult[$key]['user_detail'] = $vo['user']['nickname'] . '<br />' . $vo['user']['phone'];
                $selectResult[$key]['buyer_detail'] =$vo['consignee']. '<br />' .$vo['phone'];
                $selectResult[$key]['address_detail'] =$vo['province'].$vo['city'].$vo['area'].$vo['detail'];
                $selectResult[$key]['voucher'] = '<div class="img'.$vo['id'].'" ><img layer-pid='.$vo['id'].' onclick="toBig('.$vo['id'].')" layer-src="'.$vo['img'].'" src="'.$vo['img'].'" style="height:100px;"  alt="'.$vo['user']['nickname'].'"></div>';
                $selectResult[$key]['export'] = $vo['img'];
                $selectResult[$key]['act_nickname'] = $vo['activation']['nickname'];
                $selectResult[$key]['act_phone'] = $vo['activation']['phone'];
                $selectResult[$key]['create_at'] = $vo['created_at'];
            }
            if(isset($param['excel']) && $param['excel'] == 'to_excel'){    //导出到excel
                $content = [];
                foreach($selectResult as $k=>$v){
                    $content[$k]['uid'] = $v['uid'];
                    $content[$k]['user_detail'] = $v['user_detail'];
                    $content[$k]['buyer_detail'] = $v['buyer_detail'];
                    $content[$k]['address_detail'] = $v['address_detail'];
                    $content[$k]['package_name'] = $v['package_name'];
                    $content[$k]['package_price'] = $v['package_price'];
                    $content[$k]['voucher'] = $v['voucher'];
                    $content[$k]['act_nickname'] = $v['act_nickname'];
                    $content[$k]['act_phone'] = $v['act_phone'];
                    $content[$k]['created_at'] = $v['created_at'];
                }
//                dump($content);exit;
                $excel = new Excel();
                $first = ['A1'=>'编号ID','B1'=>'用户昵称/用户手机号','C1'=>'收货人/收货人手机号','D1'=>'收货地址','E1'=>'购买套餐','F1'=>'套餐价格','G1'=>'支付凭证','H1'=>'激活人昵称','I1'=>'激活人手机号','J1'=>'下单时间'];
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