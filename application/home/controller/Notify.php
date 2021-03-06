<?php

namespace app\home\controller;

use think\Controller;
use think\Db;
use think\Request;
use Vendor\AliPay\AlipayTradeService;
use wechatH5\Notify_pub;

vendor('wechatH5.WxMainMethod');
vendor('AliPay.Config');
vendor('AliPay.AlipayTradeService');
class Notify extends Controller
{


    /**
     * 微信回调
     */
    public function wechatNotify()
    {
        $notify = new Notify_pub();
        $xml = file_get_contents("php://input");
        //写入日志
        $notify->log_result('notify_url.log', $xml);
        $notify->saveData($xml);
        if ($notify->checkSign() == TRUE) {     //验签
            $returnData = $notify->xmlToArray($xml);
            $out_trade_no = $returnData['out_trade_no'];   //订单号
            $order = Db::table('sql_shop_order')->where('order_sn', $out_trade_no)->find();
            $total_fee = $returnData['total_fee'] / 100;    //实付金额
            /*   if($total_fee != $order['amount']){
                   file_put_contents('错误信息.txt','订单id:'.$order['id'].'支付金额:'.$total_fee."\n",FILE_APPEND);
                   echo 'success';
               }*/
            if ($order['status'] == 1) {
                $res = Db::table('sql_shop_order')->where('id', $order['id'])->update(['status' => 2, 'money' => $total_fee]);
                if ($res) {
                    return 'success';
                }
                return 'fail';
            }
        }
    }


    /**
     * @return string
     * @throws \think\Exception
     * 支付宝支付回调
     */
    public function aliPayNotify()
    {
        $arr = $_POST;
        $this->log_result('ali_notify.log', json_encode($arr));
        $config = \vendor\AliPay\Config::config();
        $alipayService = new AlipayTradeService($config);
        $result = $alipayService->check($arr);
        if ($result) {
            $orderCode = htmlspecialchars($arr['out_trade_no']);
            $order = Db::table('sql_shop_order')->where('order_sn', $orderCode)->find();
            if ($arr['trade_status'] == 'TRADE_SUCCESS') {
                $total_fee = $arr['total_amount'];
                $res = Db::table('sql_shop_order')->where('id', $order['id'])->update(['status' => 2, 'money' => $total_fee]);
                if ($res) {
                    return 'success';
                }
                return 'fail';
            }
        }
    }

    // 打印log
    public function log_result($file, $word)
    {
        $fp = fopen($file, "a");
        flock($fp, LOCK_EX);
        fwrite($fp, "执行日期：" . strftime("%Y-%m-%d-%H：%M：%S", time()) . "\n" . json_encode($word) . "\n\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }








}



