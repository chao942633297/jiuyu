<?php

namespace app\home\controller;

use think\Controller;
use think\Db;
use think\Request;
use Vendor\AliPay\AlipayFundTransToaccountTransferRequest;
use Vendor\AliPay\AlipayTradeService;
use Vendor\AliPay\AlipayTradeWapPayContentBuilder;
use Vendor\AliPay\AopClient;
use Vendor\AliPay\Config;

vendor('AliPay.Config');
vendor('AliPay.AopClient');
vendor('AliPay.AlipayTradeService');
vendor('AliPay.AlipayTradeWapPayContentBuilder');
vendor('AliPay.AlipayFundTransToaccountTransferRequest');
class Alipay extends Controller{

    public function webPay($orderId){             //支付宝支付 普通订单
        if(empty($orderId)){
            return json(['msg'=>'参数错误','code'=>'1001']);
        }
        $order = Db::name('shop_order')->where('id',$orderId)->find();
        $body = '商城购物';
        $subject = '玖誉商城';
        $out_trade_no = $order['order_sn'];
        $total_amount = $order['amount'];
          #TODO 测试金额
        $total_amount = 0.01;
        $timeout_express = '1m';
        $config = Config::config();
        $payRequestBuilder = new AlipayTradeWapPayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setTimeExpress($timeout_express);
        $payResponse = new AlipayTradeService($config);
        $payResponse->wapPay($payRequestBuilder,$config['return_url'],$config['notify_url']);
    }


    public function webSpyPay($orderId){             //支付宝支付 窥探支付
        if(empty($orderId)){
            return json(['msg'=>'参数错误','code'=>'1001']);
        }
        $order = Db::name('shop_spy_record')->where('id',$orderId)->find();
        $body = '商城窥探';
        $subject = '玖誉商城';
        $out_trade_no = $order['order_sn'];
        $total_amount = $order['amount'];
          #TODO 测试金额
        $total_amount = 0.01;
        $timeout_express = '1m';
        $config = Config::config();
        $payRequestBuilder = new AlipayTradeWapPayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setTimeExpress($timeout_express);
        $payResponse = new AlipayTradeService($config);
        $config['return_url'] = "http://admin.jiuyushangmao.com/success";
        $config['notify_url'] = "http://admin.jiuyushangmao.com/home/Notify/aliPaySpyNotify";
        $payResponse->wapPay($payRequestBuilder,$config['return_url'],$config['notify_url']);
    }



    public function withDraw($data){            //支付宝转账

        $config = Config::config();
        $aop = new AopClient($config);
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2017122201066532';
        $aop->rsaPrivateKey = $config['merchant_private_key'];
        $aop->alipayrsaPublicKey=$config['alipay_public_key'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';

        $txmoney = $data['money'];
        $out_biz_no = $data['withdraw_sn'];
        $payee_account = $data['alipay_account'];
        $payee_man = $data['alipay_name'];

        $request = new AlipayFundTransToaccountTransferRequest();
        $request->setBizContent("{" .
            "\"out_biz_no\":\"".$out_biz_no."\"," .
            "\"payee_type\":\"ALIPAY_LOGONID\"," .
            "\"payee_account\":\"".$payee_account."\"," .
            "\"amount\":\"".$txmoney."\"," .
            "\"payer_show_name\":\"玖誉商城提现\"," .
            "\"payee_real_name\":\"".$payee_man."\"," .
            "\"remark\":\"余额提现\"" .
            "  }");
        $result = $aop->execute ($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        return $result->$responseNode;
//        $resultCode = $result->$responseNode->code;
//         dump($result->$responseNode);
//         dump($resultCode);
    }


}