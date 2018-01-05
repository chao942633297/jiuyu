<?php

namespace app\backsystem\model;


use think\Model;

class UserRelationModel extends Model{

    protected  $table = 'sql_user_relation';



    public function user(){
        $this->belongsTo(UserModel::class,'user_id');
    }



}

