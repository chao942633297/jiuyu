<?php

namespace app\home\validate;


use think\Validate;

class Address extends Validate{


    protected $rule = [
            'consignee' => 'require|max:20',
            'mobile' => 'require|/^1[34578]\d{9}$/',
            'province'=>'require|max:50',
            'city'=>'require|max:100',
            'area'=>'require|max:100',
            'detail' => 'require|max:100'
    ];

    protected  $message = [
        'consignee.require' =>'收货人姓名不能为空',
        'consignee.max:20' =>'收货人姓名不能超过20个字符',
        'mobile.require' => '手机号不能为空',
        'mobile./^1[34578]\d{9}$/' => '手机号格式错误',
        'province.require'=>'省份不能为空',
        'province.max:50'=>'省份最多不能超过50个字符',
        'city.require'=>'城市不能为空',
        'city.max:100'=>'城市最多不能超过100个字符',
        'area.require'=>'区/县不能为空',
        'area.max:100'=>'区/县最多不能超过100个字符',
        'detail.require' => '详细地址不能为空',
        'detail.max:100' => '详细地址不能超过100个字符'
    ];


    protected $scene = [
        'update' => [
            'consignee.msx:20',
            'mobile./^1[34578]\d{9}$/',
            'province.max:50',
            'city.max:100',
            'area.max:100'
        ],
        'register_address' => [
            'consignee',
            'mobile',
            'detail'
        ],
    ];






}
