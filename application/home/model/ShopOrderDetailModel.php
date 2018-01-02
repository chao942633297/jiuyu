<?php
namespace app\home\model;

use think\Model;

class ShopOrderDetailModel extends Model
{
    protected $table = 'sql_shop_order_detail';

    /**
     * 根据搜索条件获取商城订单列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @param $order
     * @param $field
     */
    public function getShopOrderDetailByWhere($where, $offset, $limit,$order='id desc',$field='*')
    {
         return $this->where($where)->field($field)->limit($offset, $limit)->order($order)->select();
    }

    /**
     * 根据搜索条件获取所有的商城订单数量
     * @param $where
     */
    public function getAllShopOrderDetail($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入商城订单详情
     * @param $param 二维数组 
     */
    public function addShopOrderDetail($param)
    {
        try{
            $where = array();
            $where['name'] = $param['name'];
            $where['cid'] = $param['cid'];
            $exist = $this->where($where)->find();
            if ($exist) {
                return ['code' => -3, 'data' => '', 'msg' => '此分类下订单名称已存在'];
            }
            $result =  $this->validate('ShopOrderDetailValidate')->insert($param);
             
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加商城订单成功'];
            }
        }catch( PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑商城订单信息
     * @param $param
     */
    public function editShopOrderDetail($param)
    {
        try{
            $result =  $this->validate('ShopOrderDetailValidate')->save($param, ['id' => $param['id']]);

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
     * 根据商城订单id获取商城订单信息
     * @param $id
     * @param $field
     */
    public function getOneShopOrderDetail($id,$field="*")
    {
        return $this->where('id', $id)->field($field)->find();
    }


    /**
     * 删除商城订单
     * @param $id
     */
    public function delShopOrderDetail($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除商城订单成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}