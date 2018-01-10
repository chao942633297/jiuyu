<?php
namespace app\backsystem\controller;

use app\backsystem\model\Node;
use think\Controller;

class Base extends Controller
{
    public function _initialize()
    {
        if(empty(session('username'))){
            $this->redirect('login/index');
        }

        //检测权限
        $control = lcfirst( request()->controller() );
        $action = lcfirst( request()->action() );

        //跳过登录系列的检测以及主页权限
        if(!in_array($control, ['login', 'index'])){

            if(!in_array($control . '/' . $action, session('action'))){
                $this->error('没有权限');
            }
        }

        //获取权限菜单
        $node = new Node();

        $this->assign([
            'username' => session('username'),
            'menu' => $node->getMenu(session('rule')),
            'rolename' => session('role')
        ]);

    }

    public function isLogin(){
        return json(['data'=>session('username'),'msg'=>'登陆状态','code'=>200]);
    }

    public function outLogin(){
        session_destroy();
        return json(['msg'=>'推出成功','code'=>200]);
    }

}
