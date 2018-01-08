<?php

namespace app\home\controller;

use app\backsystem\model\AccountModel;
use app\backsystem\model\GoodsModel;
use app\backsystem\model\OrderModel;
use app\backsystem\model\UserModel;
use think\Controller;
use think\Db;
use think\Exception;
use think\Loader;
use think\Request;

class Buycar extends Base
{
    protected $userId;

    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->userId = session('home_user_id');
    }

    /**
     * @param Request $request
     * @return \think\response\Json
     * 我要购车
     * 提交订单页面
     */
    public function index(Request $request){
        //一个账号只能购买一辆车
        $count = Db::table('sql_order')
            ->where(['uid'=>$this->userId,'status'=>['EGT',2]])->count();
        if($count >= 1){
            return json(['msg'=>'一个账号只能购买一辆车','code'=>1001]);
        }
        $carId = $request->param('carId');
        if(empty($carId)){
            $carId = session('home_car_id');
        }else{
            session('home_car_id',$carId);
        }
        if(empty($carId)){
            return json(['msg'=>'参数错误','code'=>1001]);
        }
        $goods = GoodsModel::get($carId);
        $return['carName'] = $goods['name'];
        $return['carPrice'] = $goods['price'];
        $return['carNumber'] = 1;

        if($request->has('addrId')){
            $where['id'] = $request->param('addrId');
        }else{
            $where['uid'] = $this->userId;
        }
        $address = Db::table('sql_address')
            ->field('consignee,phone,province,city,area,detail')
            ->where($where)->order('is_default','desc')->find();
        $return['balance'] = db('users')->where('id',$this->userId)->value('balance');
        return json(['data'=>['carDetail'=>$return,'addr'=>$address],'msg'=>'查询成功','code'=>200]);
    }


    //执行我要购车
    public function actBuyCar(Request $request){
        //一个账号只能购买一辆车
        $count = db('order')->where('uid',$this->userId)->count();
        if($count >= 1){
            return json(['msg'=>'一个账号只能购买一辆车','code'=>1001]);
        }
        //执行购买车辆
        $input = $request->post();
        $validate = Loader::validate('Goods');
        if(!$validate->check($input)){
           return json(['msg'=>$validate->getError(),'code'=>1001]);
        }
        $good = GoodsModel::get($input['carId']);
        if(!$good){
            return json(['msg'=>'所选车型不存在','code'=>1002]);
        }
        $address = Db::table('sql_address')->where('id',$input['addrId'])->find();
        $user = UserModel::get($this->userId);
        if($user['two_password'] !== md5($input['password'])){
            return json(['msg'=>'支付密码不正确','code'=>1003]);
        }
        if($good['price'] > $user['balance']){
            return json(['msg'=>'余额不足,请选购其他车辆','code'=>1004]);
        }else if($good['price'] < 0){
            return json(['msg'=>'车辆金额错误','code'=>1006]);
        }
        Db::startTrans();
        try{
            //扣除用户余额
            UserModel::get($this->userId)->setDec('balance',$good['price']);

            //添加订单
            $data = [];
            $data['uid'] = $this->userId;
            $data['order_sn'] = orderNum();
            $data['buyer_name'] = $address['consignee'];
            $data['buyer_phone'] = $address['mobile'];
            $data['buyer_address'] = $address['province'].$address['city'].$address['area'].$address['detail'];
            $data['price'] = $good['price'];
            $data['good_name'] = $good['name'];
            $data['good_img'] = $good['img'];
            $data['good_price'] = $good['price'];
            $data['status'] = 1;
            $data['payment'] = 3;
            $data['created_at'] = date('YmdHis');
            $order= OrderModel::create($data);
            //增加用户余额消费记录
            $list = AccountModel::getAccountData($this->userId,$good['price'],'购买车辆',7,2,$order['id']);
            AccountModel::create($list);
            Db::commit();
            return json(['msg'=>'提交购车成功','code'=>200]);
        }catch(Exception $e){
            Db::rollback();
            return json(['msg'=>$e->getMessage(),'code'=>1005]);
        }
    }


    /**
     * @param Request $request
     * @return \think\response\Json
     * 我的订单-购车订单
     * 传入status  1代付款2待提车3已完成
     */
    public function myOrder(Request $request){
        $status = $request->param('status',1);
        $carOrderData = Db::table('sql_order')
            ->field('id,order_sn,good_name,good_price,good_img,price')
            ->where(['uid'=>$this->userId,'status'=>$status])
            ->order('id','desc')->select();

        return json(['data'=>$carOrderData,'msg'=>'查询成功','code'=>200]);
    }

    /**
     * @param Request $request
     * @return \think\response\Json
     * 订单详情
     * 传入订单id orderId
     */
    public function orderDetail(Request $request){
        $orderId = $request->param('orderId');
        if(empty($orderId)){
            return json(['msg'=>'参数错误','code'=>1001]);
        }
        $carOrderData = Db::table('sql_order')
            ->field('id,order_sn,buyer_name,buyer_phone,good_name,good_price,good_img,price,status,created_at')
            ->where('id',$orderId)
            ->order('id','desc')->select();
        return json(['data'=>$carOrderData,'msg'=>'查询成功','code'=>200]);
    }


    /**
     * @param Request $request
     * @return \think\response\Json
     * @throws Exception
     * 取消订单
     * 传入订单id orderId
     */
    public function cancelOrder(Request $request){
        $orderId = $request->param('orderId');
        if(empty($orderId)){
            return json(['msg'=>'参数错误','code'=>1001]);
        }
        $res = Db::table('sql_order')->where('id',$orderId)->delete();
        if($res){
            return json(['msg'=>'取消订单成功','code'=>200]);
        }
        return json(['msg'=>'取消订单失败','code'=>1002]);
    }


    /**
     * @param Request $request
     * @return \think\response\Json
     * 代付款-立即支付
     */
    public function orderPay(Request $request){
        $input = $request->post();
        if(empty($input['orderId'])){
            return json(['msg'=>'参数错误','code'=>1001]);
        }
        $orderData = Db::table('sql_order')
            ->field('id,price,status')
            ->where('id',$input['orderId'])->find();
        $user = Db::table('sql_users')->where('id',$this->userId)->find();
        if(!isset($input['password']) || md5($input['password']) !== $user['two_password']){
            return json(['msg'=>'支付密码错误','code'=>1002]);
        }
        if($user['balance'] < $orderData['price']){
            return json(['msg'=>'余额不足,请选购其他车辆','code'=>1002]);
        }
        $count = Db::table('sql_order')->where(['uid'=>$this->userId,'status'=>['EGT',2]])->count();
        if($count > 1){
            return json(['msg'=>'一个账号只能购买一辆车','code'=>1002]);
        }
        Db::startTrans();
        try{
            //扣除用户余额
            UserModel::get($this->userId)->setDec('balance',$orderData['price']);
            //增加用户余额消费记录
            $list = AccountModel::getAccountData($this->userId,$orderData['price'],'购买车辆',7,2,$orderData['id']);
            AccountModel::create($list);
            Db::commit();
            return json(['msg'=>'提交购车成功','code'=>200]);
        }catch(Exception $e){
            Db::rollback();
            return json(['msg'=>$e->getMessage(),'code'=>1005]);
        }
    }




}
