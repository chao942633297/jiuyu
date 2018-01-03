<?php
namespace app\backsystem\model;

use think\Model;

class ShopOrderModel extends Model
{
    protected $table = 'sql_shop_order';

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
}