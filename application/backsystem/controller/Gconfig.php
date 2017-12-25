<?php
namespace app\backsystem\controller;
use \think\Db;
class Gconfig extends Base
{
    public function index()
    {   
        $conf = unserialize(file_get_contents('./config'));
//        dump($conf);exit;
        $switch = db('config')->where('id',1)->value('switch');
        $this->assign("conf",$conf);
        $this->assign("type",$switch);
//         var_dump($conf);die;
        return $this->fetch();
    }

    public function saveConfig(){
        $param = input('param.');
        $param = parseParams($param['data']);
//         var_dump($param);die;
         $param = serialize($param);
         file_put_contents('./config',$param);
        return json(['code'=>1,'msg'=>'修改成功']);
    }

    public function switch_botton(){
        $type = input('type');          //开启true 关闭false
        $switch = 2;
        $msg = '关闭自动公排';
        if($type == 'true'){
            $switch = 1;
            $msg = '开启自动公排';
        }
        $res = db('config')->where('id',1)->update(['switch'=>$switch]);
        if($res){
            return json(['msg'=>$msg,'code'=>200]);
        }
        return json(['msg'=>'操作失败','code'=>1001]);
    }



}
