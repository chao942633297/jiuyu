<?php
namespace app\backsystem\model;

use think\Model;

class WithdrawModel extends Model
{
    protected $table = 'sql_withdraw';

    public function users(){
        return $this->belongsTo(UserModel::class,'uid','id');
    }



    /**
     * 根据搜索条件获取提现列表信息
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getWithdrawByWhere($where, $offset, $limit,$in=false)
    {
        if($in){
            return $this->where($where)->where('uid','in',$in)->limit($offset, $limit)->order('id desc')->select();
        }
        
        return $this->where($where)->limit($offset, $limit)->order('id desc')->select();
    }

    /**
     * 根据搜索条件获取所有的提现数量
     * @param $where
     */
    public function getAllWithdraw($where,$uid)
    {
        if($uid){
            return $this->where($where)->where('uid','in',$uid)->count();
        }
        return $this->where($where)->count();
    }

    /**
     * 插入管理员信息
     * @param $param
     */
    public function insertWithdraw($param)
    {
        try{

            $result =  $this->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加提现成功'];
            }
        }catch( PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑管理员信息
     * @param $param
     */
    public function editWithdraw($param)
    {
        try{

            $result =  $this->save($param, ['id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑提现成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据管理员id获取角色信息
     * @param $id
     */
    public function getOneWithdraw($id)
    {
        return $this->where('id', $id)->find();
    }

    /**
     * 删除管理员
     * @param $id
     */
    public function delWithdraw($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除管理员成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}
