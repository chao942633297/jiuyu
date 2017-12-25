<?php
namespace app\backsystem\model;

use think\Model;

class RowModel extends Model
{
    protected $table = 'sql_row';

    //订单详情
    public function user()
    {
        return $this->belongsTo(UserModel::class,'user_id');
    }





    /**
     * 组装数据
     */
    public static function getRowData($userId,$user_phone,$time,$position){
        $data = [
            'user_id'=>$userId,
            'user_phone'=>$user_phone,
            'time'=>$time,
            'position'=>$position,
            'created_at'=>date('YmdHis')
        ];
        return $data;
    }




}