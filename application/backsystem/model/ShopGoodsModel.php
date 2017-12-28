<?php
namespace app\backsystem\model;

use think\Model;

class ShopGoodsModel extends Model
{
    protected $table = 'sql_shop_goods';

    /**
     * 根据搜索条件获取商城商品列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @param $order
     * @param $field
     */
    public function getShopGoodsByWhere($where, $offset, $limit,$order='id desc',$field='*')
    {
         return $this->where($where)->field($field)->limit($offset, $limit)->order($order)->select();
    }

    /**
     * 根据搜索条件获取所有的商城商品数量
     * @param $where
     */
    public function getAllShopGoods($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入商城商品
     * @param $param
     */
    public function addShopGoods($param)
    {
        try{
            // var_dump($param);die;
            // $result =  $this->validate('UserValidate')->save($param);
            $where = array();
            $where['name'] = $param['name'];
            $where['cid'] = $param['cid'];
            $exist = $this->where($where)->find();
            if ($exist) {
                return ['code' => -3, 'data' => '', 'msg' => '此分类下商品名称已存在'];
            }
            $result =  $this->validate('ShopGoodsValidate')->insert($param);
             
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加商城商品成功'];
            }
        }catch( PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑商城商品信息
     * @param $param
     */
    public function editShopGoods($param)
    {
        try{
            $result =  $this->validate('ShopGoodsValidate')->save($param, ['id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑商城商品成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据商城商品id获取商城商品信息
     * @param $id
     * @param $field
     */
    public function getOneShopGoods($id,$field="*")
    {
        return $this->where('id', $id)->field($field)->find();
    }


    /**
     * 删除商城商品
     * @param $id
     */
    public function delShopGoods($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除商城商品成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}