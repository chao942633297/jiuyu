<?php

namespace app\home\controller;


use app\backsystem\model\ShopOrderModel;
use think\Controller;
use think\Request;
use wechatH5\JsApi_pub;
use wechatH5\UnifiedOrder_pub;
use wechatH5\WxPayConf_pub;

vendor('wechatH5.WxMainMethod');
vendor('wechatH5.WxPayConf_pub');
class Wxpay extends Controller
{

    //微信支付
    public function wechatPay(Request $request)
    {
        $jsApi = new JsApi_pub();
        $orderId = $request->param('orderId');
        if (empty($orderId)) {
            exit("<script> alert('缺少主键');history.back(); </script>");
        }
        $orderData = ShopOrderModel::get($orderId);
        if (!$orderData) {
            exit("<script> alert('该订单不存在!');history.back(); </script>");
        }
        $openid = $orderData['user']['openid'];
        if (empty($openid)) {
            if (empty($_GET['code'])) {
//            触发微信返回code码
                $url = $jsApi->createOauthUrlForCode(WxPayConf_pub::JS_API_CALL_URL . '?orderId=' . $orderId);
                Header("Location: $url");
                exit();
            } else {
//            获取code码，以获取openid
                $orderId = $_GET['orderId'];
                $code = $_GET['code'];
                $jsApi->setCode($code);
                $openid = $jsApi->getOpenId();
            }
        }
        $orderData = ShopOrderModel::get($orderId);
        $out_trade_no = $orderData['order_sn'];
     //   $total_fee = (int)$orderData['amount'] * 100;
        #TODO 测试金额
        $total_fee = 1;

        $unifiedOrder = new UnifiedOrder_pub();
        $unifiedOrder->setParameter("openid", "$openid");//商品描述
        $unifiedOrder->setParameter("body", "玖誉商城");//商品描述
        $unifiedOrder->setParameter("out_trade_no", "$out_trade_no");//商户订单号
        $unifiedOrder->setParameter("total_fee", $total_fee);//总金额
        $unifiedOrder->setParameter("notify_url", WxPayConf_pub::NOTIFY_URL);//通知地址
        $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);

        $jsApiParameters = $jsApi->getParameters();
//         dump($jsApiParameters);
        // return;
        $this->assign('jsApiParameters', $jsApiParameters);

        return view('wechat/wxchatpay');
    }



}

