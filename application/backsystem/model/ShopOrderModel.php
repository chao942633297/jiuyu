<?php
namespace app\backsystem\model;


use app\backsystem\model\ShopGoodsModel;

use think\Model;
use think\Db;

class ShopOrderModel extends Model
{
    protected $table = 'sql_shop_order';


    public function user(){
        return $this->belongsTo(UserModel::class,'uid');
    }


    /**
     * 根据搜索条件获取商城订单列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @param $order
     * @param $field
     */
    public function getShopOrderByWhere($where, $offset, $limit,$order='id desc',$field='*')
    {
         return $this->where($where)->field($field)->limit($offset, $limit)->order($order)->select();
    }

    /**
     * 根据搜索条件获取商城订单列表信息 没有分页
     * @param $where
     * @param $order
     * @param $field
     */
    public function getShopOrder($where,$order='id desc',$field='*')
    {
         return $this->where($where)->field($field)->order($order)->select();
    }

    /**
     * 根据搜索条件获取所有的商城订单数量
     * @param $where
     */
    public function getAllShopOrder($where)
    {
        return $this->where($where)->count();
    }


    /**
     * 订单发货处理 添加运单号等信息
     * @param $param
     */
    public function editShopOrder($param)
    {
        try{
            $result =  $this->save($param, ['id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑商城订单成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    /**
     * 插入商城订单shop_order 同时插入shop_order_detail
     * @param $param 
     * @param $param['goodsinfo']   array(array('goodsid'=>**,'goodsnum'=>**),array('goodsid'=>**,'goodsnum'=>**),)
     */
    public function addShopOrder($param)
    {
        Db::startTrans();
        try{
            $goodsinfo = $param['goodsinfo'];
            unset($param['goodsinfo']);
            $param['created_at'] = date('Y-m-d H:i:s',time());
            // 计算总价格插入到shop_order中  订单付款时需重新计算订单价格
            $param['amount'] = $this->sumGoods($goodsinfo);
      
            // $result = $this->validate('Shoporder')->insert($param);
            $result = Db::table('sql_shop_order')->insert($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                //生成插入订单详情数据 数据为生成订单时 的信息 ，防止未付款时期商品删除时订单商品异常，订单付款时需重新更新商品信息
                $detailData = array(); 
                foreach ($goodsinfo as $key => $value) {
                    $goodsdata = db('shop_goods')->field('name,price,cid,unit,imgurl,remark,description')->find($value['goodsid']);
                    $detailData[$key]['goodsname'] = $goodsdata['name'];
                    $detailData[$key]['price'] = $goodsdata['price'];
                    $detailData[$key]['cid'] = $goodsdata['cid'];
                    $detailData[$key]['unit'] = $goodsdata['unit'];
                    $detailData[$key]['imgurl'] = $goodsdata['imgurl'];
                    $detailData[$key]['remark'] = $goodsdata['remark'];
                    $detailData[$key]['description'] = $goodsdata['description'];
                    
                    $detailData[$key]['order_sn'] = $param['order_sn'];
                    $detailData[$key]['goodsid'] = $value['goodsid'];
                    $detailData[$key]['goodsnum'] = $value['goodsnum'];
                    $detailData[$key]['created_at'] = date('Y-m-d H:i:s',time());
                }

                $re = Db::table('sql_shop_order_detail')->insertAll($detailData);   
                if ($re === false) {
                     Db::rollback();
                     return ['code' => 0, 'data' => $param['order_sn'], 'msg' => '生成订单失败！'];
                }else{
                    Db::commit();
                    return ['code' => 1, 'data' => $param['order_sn'], 'msg' => '添加商城订单成功！'];
                }
            }
        }catch( PDOException $e){
            Db::rollback();
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据商城订单id获取商城订单信息 包括形单detail
     * @param $id
     * @param $field
     */
    public function getOneShopOrder($id,$field="*")
    {
        $order = $this->where('id', $id)->field($field)->find();
        $order['goodsinfo'] = db('shop_order_detail')->where('order_sn',$order['order_sn'])->select();

        return $order;
    }


    /**
     * 删除商城订单
     * @param $id
     */
    public function delShopOrder($id)
    {
        try{
            $this->where('id', $id)->update(['is_delete'=>'1']);
            return ['code' => 1, 'data' => '', 'msg' => '删除商城订单成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    //计算商品总价格
    /**
     * 计算商品总价格
     * @param $goodsdata array 计算价格 的商品Id  array(array('goodsid'=>**,'goodsnum'=>**),array('goodsid'=>**,'goodsnum'=>**),)
     */
    public function sumGoods($goodsdata)
    {
        $sum = 0;
        foreach ($goodsdata as $key => $value) {
            $re = db('shop_goods')->field('price')->where('id',$value['goodsid'])->find();
            $sum1 = $re['price']*$value['goodsnum'];
            $sum += $sum1;
        }
        return $sum;
    }


    /**
     * 根据订单号 计算订单总价
     *
     * @param $order_sn 订单号
     * return $sum float 订单总金额  
     */
    public function sumGoodsByordersn($order_sn)
    {
        //检测主订单是否存在 此订单  （后台订单删除均为软删除 可不用检测）
        // $re = $this->where('order_sn', $order_sn)->find();
        // if (empty($re)) {
        //     return ['code' => 0, 'data' => '', 'msg' => '未查询到该订单'];
        // }
        $goodsinfo = db('shop_order_detail')->where('order_sn',$order_sn)->select();
        $sum  =  $this->sumGoods($goodsinfo);
        return $sum;
    }


    /**
     * 根据订单号 更新订单详情order_detail商品信息  用于未支付支付 支付过后更新商品（防止期间商品信息变动） 
     * 直接下单支付的不用调用
     *
     */
    // public function updateOrderDetail($order_sn)
    // {
    //     $goodsinfo = db('shop_order_detail')->where('order_sn',$order_sn)->select();
    //     $newData = [];
    //     foreach ($goodsinfo as $key => $value) {
    //         $newData[$key] = model('ShopGoodsModel')->getOneShopGoods($value['goodsid'],'goodsname,price,unit,imgurl');
    //     }

    //     dump($newData);
    //     exit;
    // }



}