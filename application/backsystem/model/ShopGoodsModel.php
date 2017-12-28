<?php
namespace app\backsystem\model;

use think\Model;

class ShopGoodsModel extends Model
{
    protected $table = 'sql_shop_goods';

    /**
     * 根据搜索条件获取商品列表信息
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getShopGoodsByWhere($where, $offset, $limit,$order='id desc')
    {
         return $this->where($where)->limit($offset, $limit)->order($order)->select();
    }

    /**
     * 根据搜索条件获取所有的商品数量
     * @param $where
     */
    public function getAllShopGoods($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入商品
     * @param $param
     */
    public function addShopGoods($param)
    {
        try{
            // var_dump($param);die;
            // $result =  $this->validate('UserValidate')->save($param);
            $exist = $this->where('name',$param['name'])->find();
            if ($exist) {
                return ['code' => -3, 'data' => '', 'msg' => '此商品名称已存在'];
            }
            $result =  $this->validate('ShopGoodsValidate')->insert($param);
             
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加商品成功'];
            }
        }catch( PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑商品信息
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

                return ['code' => 1, 'data' => '', 'msg' => '编辑商品成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据商品id获取商品信息
     * @param $id
     */
    public function getOneShopGoods($id)
    {
        return $this->where('id', $id)->find();
    }


    /**
     * 删除商品
     * @param $id
     */
    public function delShopGoods($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除商品成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}