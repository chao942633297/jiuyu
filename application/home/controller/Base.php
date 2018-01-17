<?php

namespace app\home\controller;

use think\Controller;
use think\Db;
use think\Request;
use wechatH5\JsApi_pub;

vendor('wechatH5.WxMainMethod');
class Base extends Controller
{

    static $jump_url = 'http://admin.jiuyushangmao.com/home/users';

    public function _initialize()
    {
        if(session('home_user_id') < 1){
            if(is_weixin()){
                $openid = session('replay_openid');
                if(empty($openid)){
                    $jsApi = new JsApi_pub();
//            触发微信返回code码
                    if(empty($_GET['code'])){
                        $url = $jsApi->createOauthUrlForCode(static::$jump_url);
                        Header("Location: $url");exit;
                    }else{
                        $code = $_GET['code'];
                        $jsApi->setCode($code);
//            获取code码，以获取openid
                        $openid = $jsApi->getOpenId();
                    }
                }
                $user = Db::table('sql_users')
                    ->where('openid', $openid)->find();
                if (!empty($user['phone'])) {
                    session('home_user_id', $user['id']);
                    $_SESSION['home_user_id'] = $user['id'];
                }else{
                    session('replay_openid',$openid);
                    return json(['msg'=>'请绑定手机号','code'=>4000])->send();
                }
            }else{
                return json(['msg'=>'请登录','code'=>3000])->send();
            }
        }
   /*     if(session('replay_openid')) {
            $openid = session('replay_openid');
            $user = Db::table('sql_users')
                ->where('openid', $openid)->find();
            if ($user) {
                session('home_user_id', $user['id']);
                $_SESSION['home_user_id'] = $user['id'];
            }
        }*/
    }

    public function puckLogin(){
        return json(['msg'=>'已登录','code'=>200]);
    }



}
