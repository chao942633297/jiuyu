<?php
namespace app\backsystem\model;

use think\Model;

class UserModel extends Model
{
    protected $table = 'sql_users';


    public function account(){
        return $this->hasMany('AccountModel','uid','id');
    }
    public function address(){
        return $this->hasOne(AddressModel::class,'uid');
    }

    public function pusers(){                                //获取上级信息
        return $this->belongsTo(UserModel::class,'pid');
    }

    public function acts(){                                   //获取激活人信息
        return $this->belongsTo(UserModel::class,'actid');
    }

    public function alipay(){
        return $this->hasOne(UserAlipayModel::class,'user_id');
    }



    public function card(){
        return $this->hasOne(CardModel::class,'uid');
    }

    public function  apply(){                         //报单中心 地址
        return $this->hasOne(ApplyModel::class,'uid');
    }

    public function  qcode(){                         //收款码
        return $this->hasOne(QcodeModel::class,'uid');
    }

    public function voucher(){                          //支付凭证
        return $this->hasOne(VoucherModel::class,'uid');
    }

    /**
     * 根据搜索条件获取用户列表信息
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getUsersByWhere($where, $offset, $limit)
    {
        return $this->where($where)->limit($offset, $limit)->order('id desc')->select();
    }

    /**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     */
    public function getAllUsers($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入管理员信息
     * @param $param
     */
    public function insertUser($param)
    {
        try{

            $result =  $this->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加用户成功'];
            }
        }catch( PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑管理员信息
     * @param $param
     */
    public function editUser($param)
    {
        try{

            $result =  $this->save($param, ['id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑用户成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据管理员id获取角色信息
     * @param $id
     */
    public function getOneUser($id)
    {
        return $this->find($id);
    }

    /**
     * 删除管理员
     * @param $id
     */
    public function delUser($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除管理员成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}
