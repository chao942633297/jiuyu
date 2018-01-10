<?php
namespace app\backsystem\model;

use think\Model;

class ShopSpySuccessModel extends Model
{
    protected $table = 'sql_shop_spy_success';

    /**
     * 根据搜索条件获取中奖列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @param $order
     * @param $field
     */
    public function getShopSuccessByWhere($where, $offset, $limit,$order='id desc',$field='*')
    {
         return $this->where($where)->field($field)->limit($offset, $limit)->order($order)->select();
    }

    /**
     * 根据搜索条件获取所有的中奖数量
     * @param $where
     */
    public function getAllShopSuccess($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入中奖
     * @param $param
     */
    public function addShopSuccess($param)
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
            $result =  $this->validate('ShopSuccessValidate')->insert($param);
             
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加中奖成功'];
            }
        }catch( PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑中奖信息
     * @param $param
     */
    public function editShopSuccess($param)
    {
        try{
            $result =  $this->validate('ShopSuccessValidate')->save($param, ['id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑中奖成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据中奖id获取中奖信息
     * @param $id
     * @param $field
     */
    public function getOneShopSuccess($id,$field="*")
    {
        return $this->where('id', $id)->field($field)->find();
    }


    /**
     * 删除中奖
     * @param $id
     */
    public function delShopSuccess($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除中奖成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}