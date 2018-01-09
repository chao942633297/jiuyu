<?php

namespace app\home\controller;

use think\Controller;
use think\Db;
use think\Request;

class Base extends Controller
{


    public function _initialize()
    {
        if(session('replay_openid')){
            $openid = session('replay_openid');
            $user = Db::table('sql_users')
                ->where('openid',$openid)->find();
            if($user){
                session('home_user_id',$user['id']);
                $_SESSION['home_user_id'] = $user['id'];
            }
        }else if(session('home_user_id') < 1 ){
            return json(['msg'=>'请登录','code'=>3000])->send();
        }
    }



}
