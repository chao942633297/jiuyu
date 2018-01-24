<?php

namespace app\home\controller;

use think\Controller;
use think\Db;
use think\Request;
use wechatH5\JsApi_pub;

vendor('wechatH5.WxMainMethod');
class Base extends Controller
{



    public function _initialize()
    {
        if(session('home_user_id') < 1){
            if(is_weixin()){
                $url = config('back_domain') . '/home/Wechatlogin/userGrant';      //跳转微信授权
                return json(['data'=>$url,'msg'=>'请登录','code'=>4000])->send();
            }
            return json(['msg'=>'请登录','code'=>3000])->send();
        }
    }

    public function puckLogin(){
        return json(['msg'=>'已登录','code'=>200]);
    }



}
