<?php
namespace app\backsystem\model;

use think\Model;

class AccountModel extends Model{
	protected $table = 'sql_account';
    public function users()
	{
		return $this->belongsTo(UserModel::class,'uid');
	}

    public function from(){
        return $this->belongsTo(UserModel::class,'from_uid');
    }

    public function withdraw(){
        return $this->belongsTo(WithdrawModel::class,'withdraw_id')->field('status');
    }

    public static function getAccountData($userId,$money,$ramark,$type,$inc,$from_id='',$withdraw_id='',$status = 1,$once_direct = 0){
        $data = [
            'uid'=>$userId,
            'balance'=>$money,
            'remark'=>$ramark,
            'type'=>$type,
            'inc'=>$inc,
            'from_uid'=>$from_id,
            'withdraw_id'=>$withdraw_id,
            'status'=>$status,
            'once_direct'=>$once_direct,
            'create_at'=> date('YmdHis')
        ];
        return $data;
    }


}