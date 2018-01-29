<?php
namespace app\home\controller;
use app\backsystem\model\ShopOrderModel;
// use app\backsystem\model\UserModel;
use think\Controller;
use think\Request;
use think\Db;
use wechatH5\JsApi_pub;
use wechatH5\UnifiedOrder_pub;
use wechatH5\WxPayConf_pub;
vendor('wechatH5.WxMainMethod');
vendor('wechatH5.WxPayConf_pub');

class Wxpay extends Controller
{
    //微信支付 普通商城
    public function wechatPay($orderId)
    {
        $jsApi = new JsApi_pub();
        // $orderId = $request->param('orderId');
        if (empty($orderId)) {
            exit("<script> alert('缺少主键');history.back(); </script>");
        }
        $orderData = ShopOrderModel::get($orderId);
        if ($orderData['status'] == 2) {
            $reurl = config('front_domain').'/order?status=2';
            header('Location:'.$reurl);
            exit;
        }
        if (!$orderData) {
            exit("<script> alert('该订单不存在!');history.back(); </script>");
        }
        $user = Db::name('users')->find(session('home_user_id'));
        $openid = $user['openid'];
        if (empty($openid)) {
            if (empty($_GET['code'])) {
//            触发微信返回code码
                $js_url = config('back_domain').'/home/wxpay/wechatPay?orderId='.$orderId;
                $url = $jsApi->createOauthUrlForCode($js_url);
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
        // $orderData = ShopOrderModel::get($orderId);
        $out_trade_no = $orderData['order_sn'];
       $total_fee = (int)$orderData['amount'] * 100;
        #TODO 测试金额
        $total_fee = 1;
        $notify_url = config('back_domain').'/home/Notify/wechatNotify';
        $unifiedOrder = new UnifiedOrder_pub();
        $unifiedOrder->setParameter("openid", $openid);//商品描述
        $unifiedOrder->setParameter("body", "玖誉商城");//商品描述
        $unifiedOrder->setParameter("out_trade_no", $out_trade_no);//商户订单号
        $unifiedOrder->setParameter("total_fee", $total_fee);//总金额
        $unifiedOrder->setParameter("notify_url", $notify_url);//通知地址
        $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);
        $jsApiParameters = $jsApi->getParameters();
        // dump($jsApiParameters);
//         dump($jsApiParameters);
        // return;
        $this->assign('jsApiParameters', $jsApiParameters);
        return view('wechat/wxchatpay');
    }

    //微信支付 窥探商城  窥探支付
    public function webSpyPay($orderId)
    {
        $jsApi = new JsApi_pub();
        // $orderId = $request->param('orderId');
        if (empty($orderId)) {
            exit("<script> alert('缺少主键');history.back(); </script>");
        }
        $orderData = Db::name('shop_spy_record')->find($orderId);
        if ($orderData['status'] == 2) {
            $reurl = config('front_domain').'/spyGames';
            header('Location:'.$reurl);
            exit;
        }
        if (!$orderData) {
            exit("<script> alert('该订单不存在!');history.back(); </script>");
        }
        $user = Db::name('users')->find(session('home_user_id'));
        $openid = $user['openid'];
        if (empty($openid)) {
            if (empty($_GET['code'])) {
//            触发微信返回code码
                $js_url = config('back_domain').'/home/wxpay/webSpyPay?orderId='.$orderId;
                $url = $jsApi->createOauthUrlForCode($js_url);
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
        // $orderData = ShopOrderModel::get($orderId);
        $out_trade_no = $orderData['spy_sn'];
        $total_fee = (int)$orderData['amount'] * 100;
        #TODO 测试金额
        $total_fee = 1;
        $notify_url = config('back_domain').'/home/Notify/wechatspyNotify';
        $unifiedOrder = new UnifiedOrder_pub();
        $unifiedOrder->setParameter("openid", $openid);//商品描述
        $unifiedOrder->setParameter("body", "玖誉商城");//商品描述
        $unifiedOrder->setParameter("out_trade_no", $out_trade_no);//商户订单号
        $unifiedOrder->setParameter("total_fee", $total_fee);//总金额
        $unifiedOrder->setParameter("notify_url", $notify_url);//通知地址
        $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);
        $jsApiParameters = $jsApi->getParameters();
        // dump($jsApiParameters);
//         dump($jsApiParameters);
        // return;
        $url = config('front_domain').'/goodsDetails?type=spys&status=1&id='.$orderData['goodsid'];
        $this->assign('url', $url);
        $this->assign('jsApiParameters', $jsApiParameters);
        return view('wechat/wxchatspypay');
    }

    //微信支付 窥探商城 抢购微信支付
    public function wechatSpyingPay($orderId)
    {
        $jsApi = new JsApi_pub();
        // $orderId = $request->param('orderId');
        if (empty($orderId)) {
            exit("<script> alert('缺少主键');history.back(); </script>");
        }
        $orderData = Db::name('shop_spying_goods')->find($orderId);
        if ($orderData['paystatus'] == 2) {
            $reurl = config('front_domain').'/spyGames';
            header('Location:'.$reurl);
            exit;
        }
        if (!$orderData) {
            exit("<script> alert('该订单不存在!');history.back(); </script>");
        }
        $goodsInfo = Db::name('shop_goods')->find($orderData['goodsid']);
        $endtime = date("Y-m-d H:i:s",time()+($goodsInfo['countdown']*3600));
        $user = Db::name('users')->find(session('home_user_id'));
        $openid = $user['openid'];
        if (empty($openid)) {
            if (empty($_GET['code'])) {
//            触发微信返回code码
                $js_url = config('back_domain').'/home/wxpay/wechatSpyingPay?orderId='.$orderId;
                $url = $jsApi->createOauthUrlForCode($js_url);
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
        $out_trade_no = $orderData['spy_sn'];
        $total_fee = (int)$orderData['sur_price'] * 100;
        #TODO 测试金额
        $total_fee = 1;
        $notify_url = config('back_domain').'/home/Notify/wechatspyingNotify';
        $unifiedOrder = new UnifiedOrder_pub();
        $unifiedOrder->setParameter("openid", $openid);//商品描述
        $unifiedOrder->setParameter("body", "玖誉商城");//商品描述
        $unifiedOrder->setParameter("out_trade_no", $out_trade_no);//商户订单号
        $unifiedOrder->setParameter("total_fee", $total_fee);//总金额
        $unifiedOrder->setParameter("notify_url", $notify_url);//通知地址
        $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);
        $jsApiParameters = $jsApi->getParameters();
        // dump($jsApiParameters);
        // dump($endtime);
        // return;

        $this->assign('endtime', $endtime);
        $url = config('front_domain').'/success?type=spys&time='.$endtime;
        $this->assign('url', $url);
        $this->assign('jsApiParameters', $jsApiParameters);
        return view('wechat/wxchatspyingpay');
    }
}
