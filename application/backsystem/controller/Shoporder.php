<?php
// +----------------------------------------------------------------------
// | snake
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:
// +----------------------------------------------------------------------
namespace app\backsystem\controller;


use app\backsystem\model\ShopOrderModel;
use app\backsystem\controller\Excel;


class Shoporder extends Base
{
    // protected $status = ['用户取消','待付款','待发货','已发货','完成'];
    // 商城订单列表
    public function orderlist()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];


            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            $where['is_delete'] = '0'; //0：未删除订单 1：已删除订单
            if (!empty($param['starttime'])) {
                $where['created_at'] = ['>=',  $param['starttime'] ];
            }

            if (!empty($param['endtime'])) {
                $where['created_at'] = ['<=',  $param['endtime'] ];
            }

            if (!empty($param['order_sn'])) {
                $where['order_sn'] = ['like', '%' . $param['order_sn'] . '%'];
            }

            if (!empty($param['buyer_phone'])) {
                $where['buyer_phone'] = ['like', '%' . $param['buyer_phone'] . '%'];
            }

            if (!empty($param['buyer_name'])) {
                $where['buyer_name'] = ['like', '%' . $param['buyer_name'] . '%'];
            }


            if ($param['status'] !== '') {
                $where['status'] = $param['status'];
            }

            $shopOrder = new ShopOrderModel();
            // $selectResult = $shopOrder->getsOrderByWhere($where, $offset, $limit,'is_under , sort desc ,id desc');
            $selectResult = $shopOrder->getShopOrderByWhere($where, $offset, $limit);
            $status = ['用户取消','待付款','待发货','已发货','完成',''];

            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['operate'] = $this->showOperate($this->makeButton($vo['id']));
                $selectResult[$key]['status'] = $status[$vo['status']];

            }

            $return['total'] = $shopOrder->getAllShopOrder($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

  
        return $this->fetch();
    }


    /*导出订单到excel
     *
     * @param   筛选条件
     */
    public function orderToExcel()
    {
        $param = input('param.');
        $where = [];  //筛选条件和orderlist一样
        $where['is_delete'] = '0'; //0：未删除订单 1：已删除订单
        if (!empty($param['starttime'])) {
            $where['created_at'] = ['>=',  $param['starttime'] ];
        }

        if (!empty($param['endtime'])) {
            $where['created_at'] = ['<=',  $param['endtime'] ];
        }

        if (!empty($param['order_sn'])) {
            $where['order_sn'] = ['like', '%' . $param['order_sn'] . '%'];
        }

        if (!empty($param['buyer_phone'])) {
            $where['buyer_phone'] = ['like', '%' . $param['buyer_phone'] . '%'];
        }

        if (!empty($param['buyer_name'])) {
            $where['buyer_name'] = ['like', '%' . $param['buyer_name'] . '%'];
        }

        if ($param['status'] !== '') {
            $where['status'] = $param['status'];
        }

        $shopOrder = new ShopOrderModel();
        $selectResult = $shopOrder->getShopOrder($where,$order='id desc','order_sn,buyer_name,buyer_phone,amount,money,province,city,area,status,payment,waybill_name,waybill_no,created_at,remark,admin_remark');
        $selectResult = objToArray($selectResult);
        
        $status = ['用户取消','待付款','待发货','已发货','完成',''];
        $payment = ['1'=>'支付宝','2'=>'微信','3'=>'余额'];

        foreach($selectResult as $key=>$vo){
            $selectResult[$key]['status'] = $status[$vo['status']];
            $selectResult[$key]['payment'] = !empty($vo['payment']) && in_array($vo['payment'], [1,2,3])  ? $payment[$vo['payment']] : ''; 
            // $detail = db('shop_order_detail')->where('order_sn',$vo['order_sn'])->field('id,goodsid,goodsname,goodsnum,price,unit,imgurl')->select();
            $detail = db('shop_order_detail')->where('order_sn',$vo['order_sn'])->field('goodsname,goodsnum,price')->select();
            $detail = objToArray($detail);
            
            // 合并订单商品信息
            $result = [];
            array_walk_recursive($detail, function($value) use (&$result) {
                array_push($result, $value);
            });
            
            $selectResult[$key]  = array_merge($selectResult[$key],$result);           
        }

        // 商品太多 会超出26列
        $excel = new Excel();
        $first = ['订单号','收货人','手机号','总价','支付价格','省','市','街道','订单状态','支付方式','物流公司','运单号','下单时间','用户留言','管理员留言','商品名称','数量','购买时单价'];
        array_unshift($selectResult,$first);
        $excel->exportExcel('订单导出'.date("YmdHis"),$selectResult);
    }






    /*订单详情
     *
     *@param id 订单id
     */
    public function orderdetail()
    {
        $id = input('param.id');
        $orderInfo = model('ShopOrderModel')->getOneShopOrder($id);

        $status = ['用户取消','待付款','待发货','已发货','完成'];
        $orderInfo['status'] = $status[$orderInfo['status']];
        $this->assign('orderInfo',$orderInfo);
        return $this->fetch();
    }

 
    /*订单发货处理
     *
     *@param id 订单id
     */
    public function orderdeal()
    {
        if(request()->isPost()){

            $param = input('post.');
            if (empty(trim($param['waybill_no']))) {
                return json(['code'=>0, 'data'=>'', 'msg'=>'运单号不能空']);
            }
            $orderData = db('shop_order')->find($param['id']);
            if ($orderData['status'] < 2) {
                return json(['code'=>0, 'data'=>'', 'msg'=>'订单未付款不能发货']);
            }
            $param['status'] = '3'; // 订单发货 订单状态修改为“已发货”
            $flag = model('ShopOrderModel')->editShopOrder($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

        $id = input('param.id');
        $orderInfo = model('ShopOrderModel')->getOneShopOrder($id);

        $status = ['用户取消','待付款','待发货','已发货','完成'];
        $orderInfo['status'] = $status[$orderInfo['status']];
        $this->assign('orderInfo',$orderInfo);
        return $this->fetch();
        //获取订单分类信息
        // $shopOrderclass  =  model('ShopOrderClassModel')->select();
        
        // $this->assign('shopOrderclass',$shopOrderclass);
        // return $this->fetch();
    }

    
    /*订单删除
     *@param id 订单id
     *
     */
    public function OrderDel()
    {
        $id = input('param.id');

        $shopOrder = new ShopOrderModel();
        $flag = $shopOrder->delShopOrder($id);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }


    /**
     * 拼装操作按钮
     * @param $id
     * @return array
     */
    private function makeButton($id)
    {
        return [
            '详情' => [
                'auth' => 'shoporder/orderlist',
                // 'href' => url('shoporder/orderdetail', ['id' => $id]),
                'href' => url('shoporder/orderdetail', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '发货' => [
                'auth' => 'shoporder/orderdeal',
                'href' => url('shoporder/orderdeal', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            // '删除' => [
            //     'auth' => 'shoporder/orderdel',
            //     'href' => "javascript:OrderDel(" . $id . ")",
            //     'btnStyle' => 'danger',
            //     'icon' => 'fa fa-trash-o'
            // ]
        ];
    }

    /**
     * 生成操作按钮
     * @param array $operate 操作按钮数组
     */
    function showOperate($operate = [])
    {
        if(empty($operate)){
            return '';
        }

        $option = '';
        foreach($operate as $key=>$vo){
            if(authCheck($vo['auth'])){
                $option .= ' <a href="' . $vo['href'] . '"><button type="button" class="btn btn-' . $vo['btnStyle'] . ' btn-sm">'.
                    '<i class="' . $vo['icon'] . '"></i> ' . $key . '</button></a>';
            }
        }

        return $option;
    }
}
