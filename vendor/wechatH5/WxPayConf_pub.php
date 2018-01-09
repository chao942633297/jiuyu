<?php
/**
 * 	配置账号信息
 */
namespace wechatH5;

class WxPayConf_pub {
    //=======【基本信息设置】=====================================
    //微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
    const APPID = 'wx598680bf4c06fd57';
    //受理商ID，身份标识
    const MCHID = '1495102292';
    //商户支付密钥Key。审核通过后，在微信发送的邮件中查看
    const KEY = '3728cdb93ee8c7eb3ae03d6fc308f3ca';
    //JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
        const APPSECRET = 'a023a354f55ce5834d83c29812ce403b';
    //=======【交易类型设置】===================================
    //H5支付的交易类型为MWEB
//    const TRADE_TYPE= 'MWEB';

    //公众号支付的交易类型为JSAPI
//    const TRADE_TYPE= 'JSAPI';
//    //=======【JSAPI路径设置】===================================
//    //获取access_token过程中的跳转uri，通过跳转将code传入jsapi支付页面
    const JS_API_CALL_URL = 'http://admin.jiuyushangmao.com/home/wxpay/wechatPay';
//    //手动授权,跳转页面,绑定微信
    const JS_API_BIND_URL = 'http://admin.jiuyushangmao.com/home/wechatlogin/index';
    //=======【证书路径设置】=====================================
    //证书路径,注意应该填写绝对路径
    const SSLCERT_PATH = __DIR__.'/cacert/apiclient_cert.pem';
    const SSLKEY_PATH = __DIR__.'/cacert/apiclient_key.pem';
    //=======【异步通知url设置】===================================
    //异步通知url，商户根据实际开发过程设定
    const NOTIFY_URL = 'http://admin.jiuyushangmao.com/home/Notify/wechatNotify';

    //=======【curl超时设置】===================================
    //本例程通过curl使用HTTP POST方法，此处可修改其超时时间，默认为30秒
    const CURL_TIMEOUT = 30;



}
?>