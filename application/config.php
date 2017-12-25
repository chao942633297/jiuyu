<?php

return [

    //前端域名
    'front_domain'=>'http://cciev.cn/',
    'back_domain'=>'http://api.cciev.cn/',



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

    //支付宝支付配置
    "Alipay"           =>   [ # appid
        'app_id'=>'2017072007820014',
        # 商户私钥，您的原始格式RSA私钥
        'merchant_private_key'=>'MIICXAIBAAKBgQDdNPc9HC940smq3iiip1GwHUukFirspq5Ir4ajdDxdMPWQGNgiLpBsVZHdSWms10V4d/zRC117qegIgVT/J0zlmGk7VumI8R0V/I5BJQR5S+KZ38wJEVysyyLzwaHVmf32S9e94JznifAv1cDOXNFpMFFlCVlN3ZTc5Za/5z1PPQIDAQABAoGAPVlzOH+YqunLBJiYrIO7JBz73YZIYVnY/E+yB6M1GqN5d31sdA51/5W73qN9q3II0mB0vYVpZ+K3d6Rm7lz39jE9keIv3UU3pIiIn6L1bVR+SCWWYCUGqpuaPEVh7yUhWnmweEr5Dg3rkYJT81PBqihDHpz6dafWNawYhxhJhAECQQD119BlWH+/mbk626BtZsZ/st8lLBqPw1TEC0laOivXDUDsIspgx4E5IX7s9sqKOVMd7iqsplA7i4MNBihXkITLAkEA5liVw5Hx6RscwQ/O+gYISqu4a8W1yEN9Z3mVOei54yiZ82I4EhFxCMjXBgIMRwfj52I6z7nwh7qDRzzxA7kDFwJBAIswSjPm/EUNktrpGBZ4tu/75O0V4F/+1pI8VaZ5AvM59MT9GZnbuqUO+t7NB3Vk6VMr0gt4Cjr8TRFlqBeToisCQBZtd5+EHUayEhmmHWPwpGwIzjsIFAv8rkAd8W6i/z5j3KF65bS0qAnP7Ee0eVeNKB6GTO2e0BGXEmMkRt8y618CQFXKuHCTVwneBnw7JNNBGz1xiIjvNcpTuqaynG5GpEB4O22wCmJTsC2/uNlucd0ogw2OLKN15gRgZgdoCH8xqUE=',
        # 异步通知地址
        'notify_url'=>'http://youlianjingxuan.com/home/alipays/payNotify',
        # 同步跳转
        'return_url' => "http://youlianjingxuan.com/home/alipays/redir",
        # 编码格式
        'charset'=>'UTF-8',
        # 签名方式
        'sign_type' => 'RSA',
        # 支付宝网关
        'gatewayUrl'=>"https://openapi.alipay.com/gateway.do",
        # 支付宝私钥文件
        // 'rsa_private_key' => ROOT_PATH.'\private\siyao.txt',
        'rsa_private_key' => "MIICXAIBAAKBgQDdNPc9HC940smq3iiip1GwHUukFirspq5Ir4ajdDxdMPWQGNgiLpBsVZHdSWms10V4d/zRC117qegIgVT/J0zlmGk7VumI8R0V/I5BJQR5S+KZ38wJEVysyyLzwaHVmf32S9e94JznifAv1cDOXNFpMFFlCVlN3ZTc5Za/5z1PPQIDAQABAoGAPVlzOH+YqunLBJiYrIO7JBz73YZIYVnY/E+yB6M1GqN5d31sdA51/5W73qN9q3II0mB0vYVpZ+K3d6Rm7lz39jE9keIv3UU3pIiIn6L1bVR+SCWWYCUGqpuaPEVh7yUhWnmweEr5Dg3rkYJT81PBqihDHpz6dafWNawYhxhJhAECQQD119BlWH+/mbk626BtZsZ/st8lLBqPw1TEC0laOivXDUDsIspgx4E5IX7s9sqKOVMd7iqsplA7i4MNBihXkITLAkEA5liVw5Hx6RscwQ/O+gYISqu4a8W1yEN9Z3mVOei54yiZ82I4EhFxCMjXBgIMRwfj52I6z7nwh7qDRzzxA7kDFwJBAIswSjPm/EUNktrpGBZ4tu/75O0V4F/+1pI8VaZ5AvM59MT9GZnbuqUO+t7NB3Vk6VMr0gt4Cjr8TRFlqBeToisCQBZtd5+EHUayEhmmHWPwpGwIzjsIFAv8rkAd8W6i/z5j3KF65bS0qAnP7Ee0eVeNKB6GTO2e0BGXEmMkRt8y618CQFXKuHCTVwneBnw7JNNBGz1xiIjvNcpTuqaynG5GpEB4O22wCmJTsC2/uNlucd0ogw2OLKN15gRgZgdoCH8xqUE=",
        # 支付宝公钥
        'ali_public_key' => "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB"
       
    ],
    //微信配置
    "Wechat"            =>   [
        # 微信的appid
        'appid'=>'wx1ff02dc078886d29',#
        # 公众号的secret
        'secret'=>'e088f46361780dfdb618b0e5db64bf24',#
        # 登录操作函数回调链接
        'callback'=>'http://youlianjingxan.com/home/login/wechat_login.html',
        # 授权成功的回调链接
        'login_success_callback'=>'http://youlianjingxuan.com',
        # 微信支付key
        'pay_key'=>'725ca368c3cb709eba07c52166de007b',
        # 商户id
        'mchid' => '1468885602',#
        #通知回调地址
        'notify_url'=>'http://youlianjingxuan/Admin/Wechat/notify.html',
        #token定义
        'TOKEN'=>"yljx",#
    ],

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
