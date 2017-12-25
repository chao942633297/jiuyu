<?php

namespace app\home\controller;

use think\Controller;
use think\Request;

class Base extends Controller
{


    public function _initialize()
    {
        if(session('home_user_id') < 1 ){
            return json(['msg'=>'请登录','code'=>3000])->send();
        }
    }



}
