<?php

namespace app\home\controller;

use Service\Wechat;
use think\Controller;
use think\Db;
use think\Request;
use wechatH5\JsApi_pub;
use wechatH5\WxPayConf_pub;

vendor('wechatH5.WxMainMethod');
vendor('wechatH5.WxPayConf_pub');

class Wechatlogin extends Controller
{

    protected $userId;
    protected $jump_url;

    public function _initialize()
    {
        $this->userId = session('home_user_id');
        $this->jump_url = config('back_domain') . '/home/Wechatlogin/userGrant';
    }


    public function userGrant()
    {
        $jsApi = new JsApi_pub();
//            触发微信返回code码
        if (empty($_GET['code'])) {
            $url = $jsApi->createOauthUrlForCode($this->jump_url);
            Header("Location: $url");
            exit;
        } else {
            $code = $_GET['code'];
            $jsApi->setCode($code);
//            获取code码，以获取openid
            $openid = $jsApi->getOpenId();
        }
        $user = Db::table('sql_users')
            ->where('openid', $openid)->find();
        if (!empty($user['phone'])) {
            session('home_user_id', $user['id']);
            $_SESSION['home_user_id'] = $user['id'];
        } else {
            session('replay_openid', $openid);
            $url = config('front_domain') . '/register';
            header('Location:' . $url);
            die;
        }
    }


    /**
     * @return \think\response\Json
     * @throws \think\Exception
     * 用户授权,获取用户openid
     */
    public function index(Request $request)
    {
        $unique = $request->param('unique');
        $web_url = config('front_domain').'/';
        if (false !== strpos($unique, 'bind_')) {           //授权绑定openid
            $unique = substr($unique, 5);
            $user = Db::table('sql_users')
                ->where('unique', $unique)->find();
            if (empty($user['openid'])) {
                $unique = 'bind_' . $unique;
                $result = $this->getOpenid($unique);
                $user['openid'] = $result['openid'];
                $res = Db::table('sql_users')->update($user);
                if ($res) {
                    return "<script> alert('绑定成功!');window.location.href=".$web_url." </script>";
                }
                return "<script> alert('绑定失败!');window.location.href=".$web_url." </script>";
            } else {
                return "<script> alert('用户已绑定');window.location.href=".$web_url." </script>";
            }
        } else {                                      //扫码注册绑定上下级
            if (is_weixin()) {
                $result = $this->getOpenid($unique);
                if (Db::table('sql_users')->where('openid', $result['openid'])->count() < 1) {        //用户未注册过
                    $wechat_config = new WxPayConf_pub();
                    $wechat = new Wechat($wechat_config);
                    $userInfo = $wechat->getUserInfo($result['openid']);
                    $userInfo = json_decode($userInfo, true);
                    $pid = Db::table('sql_users')->where('unique', $result['unique'])->value('id');
                    $nickname = '用户' . foo(4);
                    $headimgurl = config('back_domain') . '/uploads/default.png';
                    if(!empty($userInfo['nickname'])){           //用户关注公众号,获取用户信息
                        $nickname = $userInfo['nickname'];
                        $headimgurl = $userInfo['headimgurl'];
                    }
                    $userId = Db::table('sql_users')->insertGetId([
                        'pid' => $pid,
                        'openid' => $result['openid'],
                        'nickname' => $nickname,
                        'headimgurl' => $headimgurl
                    ]);
                    $login = new Login();
                    $login->saveUserRelation($userId, $pid);
                }
                session('replay_openid', $result['openid']);
                $_SESSION['replay_openid'] = $result['openid'];
            }
            $url = config('front_domain') . '/register?prentId=' . $unique;
            header('Location:' . $url);
        }

    }

    /**
     * @param string $unique
     * @return array
     * 获取openid
     */
    public function getOpenid($unique = '')
    {
        $jsApi = new JsApi_pub();
//            触发微信返回code码
        if (empty($_GET['code'])) {
            $url = $jsApi->createOauthUrlForUserInfo(WxPayConf_pub::JS_API_BIND_URL . '?unique=' . $unique);
            Header("Location: $url");
            exit;
        } else {
            $code = $_GET['code'];
            $unique = $_GET['unique'];
            $jsApi->setCode($code);
//            获取code码，以获取openid
            $openid = $jsApi->getOpenId();
        }
        return ['unique' => $unique, 'openid' => $openid];
    }


    /**
     * 微信生成二维码
     */
    public function wechatQrcode($scene_id)
    {
        $path = './uploads/wechatQrcode/' . $scene_id . '.jpg';
        if (!file_exists($path)) {
            $config = new WxPayConf_pub();
            $wechat = new Wechat($config);
            $res = $wechat->get_qrcode($scene_id);
            if (is_object($res)) {
                dump($res);
                die;
            }
            file_put_contents($path, $res);
        }
        return $path;
    }


    /**
     * 关注公众号
     * 用户绑定微信
     */
    public function follow()
    {
        //  Wechat::valid();
        $wechat_config = new WxPayConf_pub();
        $wechat = new Wechat($wechat_config);
        $postStr = file_get_contents("php://input");
        if (!empty($postStr)) {
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
            #用户发送的消息类型判断
            switch ($RX_TYPE) {
                case "event":
                    //用户关注事件
                    if ($postObj->Event == 'subscribe') {
                        /*===========用户绑定微信=============*/
                        $openid = $postObj->FromUserName;
                        if (!empty($postObj->EventKey) && strpos($postObj->EventKey, 'bind_')) {
                            $unique = substr($postObj->EventKey, 13);
                            $user = Db::table('sql_users')
                                ->where('unique', $unique)->find();
                            if ($user && empty($user['openid'])) {
                                $user['openid'] = $openid;
                                Db::table('sql_users')->update($user);
                            }
                        } else {
                            /*=================用户扫码关注=====================*/
                            if (!empty($postObj->EventKey)) {
                                $punique = substr($postObj->EventKey, 8);
                                if (Db::table('sql_users')->where('openid', $openid)->count() < 1) {        //用户未注册过
                                    $userInfo = $wechat->getUserInfo($openid);
                                    $userInfo = json_decode($userInfo, true);
                                    $pid = Db::table('sql_users')->where('unique', $punique)->value('id');
                                    $userId = Db::table('sql_users')->insertGetId([
                                        'pid' => $pid,
                                        'openid' => $openid,
                                        'nickname' => $userInfo['nickname'],
                                        'headimgurl' => $userInfo['headimgurl']
                                    ]);
                                    $login = new Login();
                                    $login->saveUserRelation($userId, $pid);
                                }
                            }
                            //存储openid
                            session('replay_openid', $postObj->FromUserName);
                        }
                    }
                    $result = $wechat->receiveReply($postObj);
                    break;
                default:
                    $result = $wechat->receiveReply($postObj);
                    break;
            }
            echo $result;
        }
    }


    /**
     * 创建底部菜单
     */
    public function createMenu()
    {
        $wechat_config = new WxPayConf_pub();
        $wechat = new Wechat($wechat_config);
        $result = $wechat->createMenu();
        if ($result->errcode == 0 && $result->errmsg == 'ok') {
            return '菜单创建成功!';
        }
        return $result;
    }


}
