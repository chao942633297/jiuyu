<?php

return [

    //前端域名
    'front_domain'=>'http://cciev.cn/',
    'back_domain'=>'http://admin.jiuyushangmao.com',



    'url_route_on' => true,
    'trace' => [
        'type' => 'html', // 支持 socket trace file
    ],
    //各模块公用配置
    'extra_config_list' => ['database', 'route', 'validate'],
    //临时关闭日志写入
    'log' => [
        'type' => 'test',
    ],
    'DEFAULT_MODULE'=>'backsystem',
    'app_debug' => true,
    'default_filter' => ['strip_tags', 'htmlspecialchars'],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------
    'cache' => [
        // 驱动方式
        'type' => 'file',
        // 缓存保存目录
        'path' => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
        'port' => 11211,
    ],
    // 'cache' => false,
    // 'tpl_cache' => false,

    //加密串
    'salt' => 'wZPb~yxvA!ir38&Z',

    //备份数据地址
    'back_path' => APP_PATH .'../back/',

    #用户等级
    'user_class' =>[
        '1' => '路人甲',  //扫码关注注册会员（未消费会员）
        '2' => '代理合伙人',    //资格费10600
        '3' => '县级报单中心',
        '4' => '市级报单中心',
        '5' => '省级报单中心'
    ],

    #资金明细类别
    'account_type' => [
        '1' => '直推奖',
        '2' => '感恩奖',
        '3' => '小组达标',
        '4' => '好友转账',
        '5' => '余额提现',
        '6' => '后台充值',
        '7'=>'购买车辆',
        '8'=>'激活合伙人',
        '9'=>'业绩分红',
        '10'=>'冻结金额转化'
    ],

    #提现状态
    'Withdraw_status' => [
        '1' => '申请中',
        '2' => '同意',
        '3' => '拒绝',
        '4'  =>'拒绝'
    ],
    'account_inc'=>[
        '1'=>'增加',
        '2'=>'减少'
    ],

    //订单状态
    'order_status' => [
        '1' => '待提车',
        '2' => '已完成',
    ],
];
