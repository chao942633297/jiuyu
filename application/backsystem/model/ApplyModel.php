<?php

namespace app\backsystem\model;

use think\Model;

class ApplyModel extends Model
{
    //
    protected $table = 'sql_apply';

    public function user(){
        return $this->belongsTo(UserModel::class,'uid');
    }

}
