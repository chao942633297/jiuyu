<?php

namespace Service;


class Wechat
{

    protected $APPID;
    protected $SERCRET;

    protected static $token = 'wechat_jiuyu';
    protected static $reply = '你好~ 欢迎来到玖誉商城,开始疯狂购物吧!';
    protected static $menu = '{
		     "button":[
		      {
		           "type":"view",
	               "name":"商城首页",
	               "url":"www.baidu.com"
		      },
		      {
		           "type":"view",
		           "name":"平台简介",
	               "url":"www.baidu.com"
		      },
		      {
		           "name":"联系我们",
		           "sub_button":[
		            {
		               "type":"view",
		               "name":"注册流程",
	               		"url":"www.baidu.com"
		            },
		            {
		               "type":"view",
		               "name":"客服电话",
	               		"url":"www.baidu.com"
		            }]
		       }]
		}';

    public function __construct($wechat_config)
    {
        $this->APPID = $wechat_config::APPID;
        $this->SERCRET = $wechat_config::APPSECRET;
    }



    #获取AccessToken
    protected function makeAccessToken()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->APPID . '&secret=' . $this->SERCRET;
        $AccessToken = $this->https_request($url);
        $postAccountData = json_decode($AccessToken, true);
        return $postAccountData['access_token'];
    }

    #获取token
    protected function https_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    public function getQrcode($data){
//        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.self::makeAccessToken();
        $access_token = '';
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token;
        $data = json_encode($data);
        return $this->https_request($url,$data);
    }



    #创建菜单方法
    public function createMenu()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . self::makeAccessToken());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::$menu);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return $tmpInfo;
    }

    #删除菜单
    public function deleteMenu()
    {
        return file_get_contents("https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=" . self::makeAccessToken());
    }

    /*
     * 暂时设置为统一回复内容
     */
    public function receiveReply($object)
    {
        $content = self::$reply;
        $result = $this->transmitText($object, $content);
        return $result;
    }


    #验证token
    public static function valid()
    {
        $echoStr = $_GET["echostr"];
        if (self::checkSignature()) {
            echo $echoStr;
            exit;
        }
    }


    #配置服务器验证token 获取服务器参数
    public static function checkSignature()
    {
        #从GET参数中读取三个字段的值
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        #读取预定义的TOKEN
        $token = self::$token;
        #对数组进行排序
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        #对三个字段进行sha1运算
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        #判断我方计算的结果是否和微信端计算的结果相符
        #这样利用只有微信端和我方了解的token作对比,验证访问是否来自微信官方.
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /*==============================================用户事件===================================================*/

    /*
     * 接收文本消息
     */
    private function receiveText($object)
    {
        $content = "你发送的是文本，内容为：" . $object->Content;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    /*
     * 接收图片消息
     */
    private function receiveImage($object)
    {
        $content = "你发送的是图片，地址为：" . $object->PicUrl;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    /*
     * 接收语音消息
     */
    private function receiveVoice($object)
    {
        $content = "你发送的是语音，媒体ID为：" . $object->MediaId;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    /*
     * 接收视频消息
     */
    private function receiveVideo($object)
    {
        $content = "你发送的是视频，媒体ID为：" . $object->MediaId;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    /*
     * 接收位置消息
     */
    private function receiveLocation($object)
    {
        $content = "你发送的是位置，纬度为：" . $object->Location_X . "；经度为：" . $object->Location_Y . "；缩放级别为：" . $object->Scale . "；位置为：" . $object->Label;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    /*
     * 接收链接消息
     */
    private function receiveLink($object)
    {
        $content = "你发送的是链接，标题为：" . $object->Title . "；内容为：" . $object->Description . "；链接地址为：" . $object->Url;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    /*
     * 回复文本消息
     */
    public function transmitText($object, $content)
    {
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            </xml>";
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }

}

