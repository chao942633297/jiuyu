<?php
/**
 * Created by PhpStorm.
 * Order: ovo
 * Date: 2017/7/10
 * Time: 下午6:08
 */
namespace app\backsystem\controller;

use app\backsystem\model\OrderModel;
use app\backsystem\model\GoodsModel;
use Symfony\Component\HttpFoundation\Request;
use think\Db;

class Order extends Base{
    const USER = 'users';
    const ADDRESS = 'address';
    const GOODS = 'goods';
    //订单列表
    public function index()
    {
        $status = config('order_status');
        $payment = config('payment');
        if(request()->isAjax()){
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['name']) && !empty($param['name'])) {      //收货人查询
                $where['buyer_name'] = ['like','%'.$param['name'].'%'];
            }
            if (isset($param['order_sn']) && !empty($param['order_sn'])) {      //订单号
                $where['order_sn'] = ['like','%'.$param['order_sn'].'%'];
            }
            if (isset($param['phone']) && !empty($param['phone'])) {      //手机号查询
                $where['buyer_phone'] = ['like','%'.$param['phone'].'%'];
            }
            if (isset($param['user_phone']) && !empty($param['user_phone'])) {      //购买人手机号查询
                $uid = db(self::USER)->where(['phone'=>['like','%'.$param['user_phone'].'%']])->column('id');
                $where['uid'] = ['in',$uid];
            }
            //支付类型
            if (isset($param['payment']) && !empty($param['payment'])) {
                $where['payment'] = $param['payment'];
            }
            //订单状态
            if (isset($param['status']) && !empty($param['status'])) {
                $where['status'] = $param['status'];
            }
            //下单时间段
            if (isset($param['end']) && !empty($param['end']) && isset($param['start']) && !empty($param['start'])) {
                $time[1] = $param['end'].' 23:59:59';
                $time[0] = $param['start'].' 00:00:00';
                $where['created_at'] = ['between',$time];
            }

            $order = new OrderModel();
            if(isset($param['excel']) && $param['excel'] == 'to_excel'){
                $offset = 0;
                $limit = 9999;
            }
            $selectResult = $order->getOrdersByWhere($where, $offset, $limit);
            $payment = config('payment');
            foreach($selectResult as $key=>$vo){
                if($param['excel'] != 'to_excel'){
                    $selectResult[$key]['order_sn'] = '<a href="javascript:getOrder('.$vo['id'].')">'.$vo['order_sn'].'</a>';
                }
                $selectResult[$key]['user_name'] = $vo['user']['nickname'].'/'.$vo['user']['phone'];
                $selectResult[$key]['user_phone'] = $vo['buyer_name'].'/'.$vo['buyer_phone'];
                $selectResult[$key]['user_address'] = $vo['user']['address']['province'].$vo['user']['address']['city'].$vo['user']['address']['area'];
                if($vo['payment']){
                    $selectResult[$key]['payment'] = $payment[$vo['payment']];
                }else{
                    $selectResult[$key]['payment'] = '未定义';
                }

                if($param['excel'] != 'to_excel'){
                    if($vo['status'] == 1){
                        $operate = [
                            '去发货' => "javascript:orderEdit('".$vo['id']."')",
                        ];
                    }else{
                        $operate = [];
                    }
                    $selectResult[$key]['operate'] = showOperate($operate);
                }
                $selectResult[$key]['status'] = $status[$vo['status']];
            }
            if(isset($param['excel']) && $param['excel'] == 'to_excel'){    //导出到excel
                $content = json_decode(json_encode($selectResult),true);
                foreach($content as $k=>$v){
                    unset($content[$k]['id']);
                    unset($content[$k]['uid']);
                    unset($content[$k]['buyer_name']);
                    unset($content[$k]['buyer_phone']);
                    unset($content[$k]['good_img']);
                    unset($content[$k]['money']);
                    unset($content[$k]['payment']);
                    unset($content[$k]['updated_at']);
                    unset($content[$k]['user']);
                }
//                dump($content);exit;
                $excel = new Excel();
                $first = ['A1'=>'编号','B1'=>'车辆名称','C1'=>'车辆价格','D1'=>'金额','E1'=>'状态','F1'=>'下单时间','G1'=>'收货人/购买人','H1'=>'收货人/购买人手机号','I1'=>'收货地址'];
                $excel->toExcel('订单列表'.date('YmdHis'),$content,$first);
                return json(['code'=>1]);
            }
            $return['total'] = $order->getAllOrders($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        $this->assign([
            'status' => $status,
            'payment' => $payment
        ]);
        return $this->fetch();
    }

    /**
     * @return \think\response\Json
     * 给用户发车,
     *
     */
    public function orderEdit()
    {
        $order = new OrderModel();

        $save['id'] = input('id');
        $save['status'] = 2;
        $flag = $order->editOrder($save);
        if($flag){
            return json(['msg'=>'操作成功','code'=>200]);
        }
        return json(['msg'=>'操作失败','code'=>1001]);
    }





    public function getOneOrder(\think\Request $request){
        $orderId = $request->param('id');
        $detail = OrderModel::get($orderId);
        return json($detail);
    }







    //订单审核
    public function tail_money_edit(){

        $order = new OrderModel();
        $save['id'] = input('id');
        $save['updated_at'] = time();
        if(input('status') == 3){
            $save['message'] = '已驳回';
            $flag = $order->editOrder($save);
            return json($flag);
        }elseif(input('status') == 4){
            $save['status'] = input('status');
            $save['fenhong'] = 1;
            //=============================算法开始=====================================//
            $config = file_get_contents('./config');
            $config = unserialize($config)['conf'];
            $orders = db('order')->where(['id'=>$save['id']])->find();
            $user = db('users')->where(['id'=>$orders['uid']])->find();
            $price = $orders['price'];
            Db::startTrans();
            try{
                //====================进行分销====================//
                $p1 = db('users')->where(['id'=>$user['pid']])->find();
                if($p1){
                    if($price > 0){
                        $bili = $p1['class'] * 3 - 3;
                        $fan = round($price * $config[$bili] / 100,2);
                        add_account($p1['id'],$fan,'下级用户'.$user['nickname'].'的分销奖金',3,$user['id']);
                    }
                    $p2 = db('users')->where(['id'=>$p1['pid']])->find();
                    if($p2){
                        if($price > 0){
                            $bili = $p2['class'] * 3 - 2;
                            $fan = round($price * $config[$bili] / 100,2);
                            add_account($p2['id'],$fan,'下级用户'.$user['nickname'].'的分销奖金',3,$user['id']);
                        }
                        $p3 = db('users')->where(['id'=>$p2['pid']])->find();
                        if($p3){
                            if($price > 0){
                                $bili = $p3['class'] * 3 - 1;
                                $fan = round($price * $config[$bili] / 100,2);
                                add_account($p3['id'],$fan,'下级用户'.$user['nickname'].'的分销奖金',3,$user['id']);
                            }
                        }
                    }
                }
                //====================分销结束====================//
                //=============进行升级==============
                if($orders['is_new'] == 1 && $user['class'] < 2){
                    if($price == 6800 || $price == 9800){
                        db('users')->where(['id'=>$user['id']])->update(['class'=>2]);
                    }
                }else{
                    shengji($user['id'],$price);
                }
                //=============升级结束===============
                // 提交事务
                Db::commit();
                $flag = $order->editOrder($save);
                return json($flag);
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return json(['code'=>0,'data'=>'','msg' =>'提交失败']);
            }

            //==============================算法结束====================================//
        }

    }


}