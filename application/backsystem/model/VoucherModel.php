<?php

namespace app\backsystem\model;

use think\Model;

class VoucherModel extends Model
{
    //
    protected $table = 'sql_voucher';


    public function user(){
        return $this->belongsTo(UserModel::class,'uid');
    }

    public function activation(){
        return $this->belongsTo(UserModel::class,'actid');
    }



    /**
     * 根据搜索条件获取用户列表信息
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getVoucherByWhere($where, $offset, $limit)
    {
        return $this->where($where)->limit($offset, $limit)->order('id desc')->select();
    }


    /**
     * 根据管理员id获取角色信息
     * @param $id
     */
    public function getAllData($where,$uids)
    {
        if($uids){
            return $this->where($where)->where('uid','in',$uids)->count();
        }
        return $this->where($where)->count();
    }


    public static function getVoucherData($userId,$actId,$money,$type,$pay_type,$name,$price,$img,$consignee,$phone,$province,$city,$area,$detail){
        $data = [
            'uid'=>$userId,
            'actid'=>$actId,
            'money'=>$money,
            'type' => $type,
            'pay_type' => $pay_type,
            'package_name' => $name,
            'package_price' =>$price,
            'package_img' =>$img,
            'package_number' => 1,
            'consignee' => $consignee,
            'phone' => $phone,
            'province' => $province,
            'city' =>$city,
            'area' =>$area,
            'detail' => $detail,
            'status' => 1,
            'created_at' => date('YmdHis')
        ];
        return $data;
    }



}
