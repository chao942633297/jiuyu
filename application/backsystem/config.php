<?php

return [

    //模板参数替换
    'view_replace_str'       => array(
        '__CSS__'    => '/static/admin/css',
        '__JS__'     => '/static/admin/js',
        '__IMG__' => '/static/admin/images',
    ),

    //管理员状态
    'user_status' => [
        '1' => '正常',
        '2' => '禁止登录'
    ],
    //角色状态
    'role_status' => [
        '1' => '启用',
        '2' => '禁用'
    ],
    //商品状态
    'goods_status' => [
        '1' => '销售中',
        '2' => '已下架'
    ],

    //支付类型
    'payment' => [
        '1' => '支付宝',
        '2' => '微信',
        '3' => '余额'
    ],

      #提现方式
    'Withdraw_type' => [
        '1' => '微信',
        '2' => '支付宝',
        '3' => '银行卡'
    ],

];
