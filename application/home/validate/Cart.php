<?php
namespace app\home\validate;



use think\Validate;


class Cart extends Validate{

    protected $rule = [
        'goodsid'=>'require',
        'userid'=>'require',
        'goodsnum'=>'require',
        'created_at'=>'require',
    ];

}




