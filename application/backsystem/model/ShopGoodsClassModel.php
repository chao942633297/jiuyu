<?php
namespace app\backsystem\model;

use think\Model;

class ShopGoodsClassModel extends Model
{
    protected $table = 'sql_shop_goods_class';

    /**
     * 根据搜索条件获取商品分类列表信息
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getShopGoodsClassByWhere($where, $offset, $limit)
    {
         return $this->where($where)->limit($offset, $limit)->order('id desc')->select();
    }

    /**
     * 根据搜索条件获取所有的商品分类数量
     * @param $where
     */
    public function getAllShopGoodsClass($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入商品分类
     * @param $param
     */
    public function addShopGoodsClass($param)
    {
        try{
            // var_dump($param);die;
            // $result =  $this->validate('UserValidate')->save($param);
            $exist = $this->where('classname',$param['classname'])->find();
            if ($exist) {
                return ['code' => -3, 'data' => '', 'msg' => '此分类已存在'];
            }
            $result =  $this->insert($param);
             
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加商品分类成功'];
            }
        }catch( PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑商品分类信息
     * @param $param
     */
    public function editShopGoodsClass($param)
    {
        try{

            $result =  $this->save($param, ['id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑商品分类成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据商品分类id获取商品分类信息
     * @param $id
     */
    public function getOneShopGoodsClass($id)
    {
        return $this->where('id', $id)->find();
    }


    /**
     * 删除商品分类
     * @param $id
     */
    public function delShopGoodsClass($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除商品分类成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}