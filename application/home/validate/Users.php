<?php
namespace app\home\validate;



use think\Validate;


class Users extends Validate{

    protected $rule = [
        'province'=>'require|max:50',
        'city'=>'require|max:100',
        'area'=>'require|max:100',
        'phone'=>'require|/^1[34578]\d{9}$/|unique:users',
        'code'=>'require|number',
        'password'=>'require'
    ];

    protected $message = [
        'province.require'=>'省份不能为空',
        'province.max:50'=>'省份最多不能超过50个字符',
        'city.require'=>'城市不能为空',
        'city.max:100'=>'城市最多不能超过100个字符',
        'area.require'=>'区/县不能为空',
        'area.max:100'=>'区/县最多不能超过100个字符',
        'phone.require'=>'手机号不能为空',
        'phone./^1[34578]\d{9}$/'=>'手机号格式错误',
        'phone.unique'=>'该手机号已注册',
        'password.require'=>'支付密码不能为空'
    ];

    protected $scene = [
        'register'  => [
            'phone',
            'code'
        ],
    ];


}




