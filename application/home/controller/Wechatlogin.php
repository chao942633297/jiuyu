<?php

namespace app\home\controller;

use Service\Wechat;
use think\Controller;
use think\Db;
use wechatH5\JsApi_pub;
use wechatH5\WxPayConf_pub;

vendor('wechatH5.WxMainMethod');
vendor('wechatH5.WxPayConf_pub');
class Wechatlogin extends Base{

    protected $userId;

    public function _initialize()
    {
        $this->userId = session('home_user_id');
        $this->userId = 1;
    }


    /**
     * @return \think\response\Json
     * @throws \think\Exception
     * 用户授权,获取用户openid
     */
    public function index(){
        $user = Db::table('sql_users')
            ->where('id',$this->userId)->find();
        if(empty($user['openid'])){
            $jsApi = new JsApi_pub();
//            触发微信返回code码
            if(empty($_GET['code'])){
                $url = $jsApi->createOauthUrlForUserInfo(WxPayConf_pub::JS_API_BIND_URL);
                Header("Location: $url");exit;
            }else{
                $code = $_GET['code'];
                $jsApi->setCode($code);
//            获取code码，以获取openid
                $openid = $jsApi->getOpenId();
            }
            $user['openid'] = $openid;
            $res = Db::table('sql_users')->update($user);
            if($res){
                return json(['msg'=>'绑定成功','code'=>200]);
            }
            return json(['msg'=>'绑定失败','code'=>1002]);
        }else{
            return json(['msg'=>'用户已绑定微信','code'=>2001]);
        }

    }


    /**
     * 关注公众号
     */
    public function follow(){
      //  Wechat::valid();
        $wechat_config = new WxPayConf_pub();
        $wechat = new Wechat($wechat_config);
        $postStr = file_get_contents("php://input");
        if (!empty($postStr)) {
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
            #用户发送的消息类型判断
            $result = true;
            switch ($RX_TYPE) {
                case "event":
                    //用户关注事件
                    if ($postObj->Event == 'subscribe') {
                        //存储openid
                        session('replay_openid',$postObj->FromUserName);
                        $result = $wechat->receiveReply($postObj);
                    }
//                    $result = $wechat->receiveFollow($postObj);
                    break;
                /* case "text":  	 	#文本消息
                     $result = $this->receiveText($postObj);
                     break;
                 case "image": 		#图片消息
                     $result = $this->receiveImage($postObj);
                     break;
                 case "voice":  		#语音消息
                     $result = $this->receiveVoice($postObj);
                     break;
                 case "video":  		#视频消息
                     $result = $this->receiveVideo($postObj);
                     break;
                 case "location":	#位置消息
                     $result = $this->receiveLocation($postObj);
                     break;
                 case "link":   		#链接消息
                     $result = $this->receiveLink($postObj);
                     break;*/
                default:
//                    $result = $wechat->receiveReply($postObj);
                    break;
            }
            echo $result;
        }
    }


    /**
     * 创建底部菜单
     */
    public function createMenu(){
        $wechat_config = new WxPayConf_pub();
        $wechat = new Wechat($wechat_config);
        $access_token = $wechat->makeAccessToken();
        $result = $wechat->createMenu($access_token);
        dump($result);
    }



}
