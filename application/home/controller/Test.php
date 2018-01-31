<?php
/**
 * Created by PhpStorm.
 * User: ovo
 * Date: 2017/7/12
 * Time: 下午6:39
 */
namespace app\home\controller;
use app\backsystem\model\CodeModel;
use app\backsystem\model\RowModel;
use app\backsystem\model\VoucherModel;
use Service\Wechat;
use think\Controller;
use think\Db;
use think\Exception;
use think\Log;
use think\Request;
use think\Validate;
use wechatH5\WxPayConf_pub;

vendor('wechatH5.WxMainMethod');
vendor('wechatH5.WxPayConf_pub');
class Test extends Validate{
    #判断微信
    public function ww(){
        if(is_weixin()){
            return json(['code'=>1]);
        }else{
            return json(['code'=>0]);
        }
    }

    public function getConfig(){
        $config = file_get_contents('config');
        $config = unserialize($config);
        dump($config);
    }


    public function index(Request $request){
        $fourId = [
           128,129,129,136
        ];
        db('account')->where(['uid' =>1, 'type' => 2, 'from_uid' => ['in', $fourId], 'status' => 1])->update(['status' => 2]);
    }







    #app下载页面
    public function appx(){
        return view('html/firstye');
    }




    public function test(){
        $openid = 'oknGV1WAwj3NNXPFkEGuR2oAofhk';
        $wechat_config = new WxPayConf_pub();
        $wechat = new Wechat($wechat_config);
        $userInfo = $wechat->getUserInfo($openid);
        dump($userInfo);
    }

    public function z(){
        $prentId = '';
        $row = 'sql_rowA';
        $rebate = new Rebate();
        $res = $rebate->getPosition($prentId,$row);
        dump($res);
    }







    public function startTrans(){
        Db::startTrans();
        try{

        }catch(Exception $e){

        }

    }

    #post 远程获取
    public function postApp(){
        $url = 'http://www.appbsl.com/app_download/mobile_down/id-126611-downloadsite-%27%2Btype%2B%27.html';
        $data = '';
        return json(http($url,$data,'POST'));
    }
    #老用户绑定
    public function bind(){
        if(request()->isPost()){
            $phone = input('phone');
            $pwd = input('pwd');
            $code = input('code');
            if($code != session('home.code')){
            	return json(['status'=>2,'data'=>'验证码错误']);
            }
            $user = db('users')->where(['phone'=>$phone])->find();
            if(empty($user)){
            	return json(['status'=>3,'data'=>'该用户还未注册，赶快去绑定手机号吧！']);
            }
            if(!empty($user['openid'])){
				return json(['status'=>2,'data'=>'该手机号已被绑定']);
            }
            if($user['password'] != md5($pwd)){
            	return json(['status'=>2,'data'=>'密码错误']);
            }
            $wxuser = session('home.user');
            if($wxuser['phone']){
            	return json(['status'=>2,'data'=>'你已经绑定过手机号了']);
            }
			$wx['openid'] = session('home.user')['openid'];
            # 性别 1=男 2=女性 0=未设置
            $wx['sex'] = session('home.user')['sex'];
            # 用户昵称
            $wx['nickname'] = session('home.user')['nickname'];
            # 头像
            $wx['headimgurl'] = session('home.user')['headimgurl'];
            db('users')->where(['id'=>$user['id']])->update($wx);
            db('users')->where(['id'=>session('home.user')['id']])->delete();
            $newuser = db('users')->where(['phone'=>$phone])->find();
            session('home.user',null);
            session('home.user',$newuser);
            return json(['status'=>1,'data'=>'绑定成功']);
        }
        return view('html/bind');
    }

    #是否显示老用户绑定
    public function judge(){
    	if(is_weixin() && !empty(session('home.user'))){
    		return json(['status'=>1]);
    	}else{
    		return json(['status'=>2]);
    	}
    }
    #加用户
    public function add(){
       /* $data = [
                0=>['n'=>'齐艳红','p'=>'15169412099'],
                1=>['n'=>'齐艳红','p'=>'13563694839'],
                2=>['n'=>'孙庆刚','p'=>'15863269000'],
                3=>['n'=>'齐君','p'=>'14768602625'],
                4=>['n'=>'王新燕','p'=>'13791853868'],
                5=>['n'=>'张硕','p'=>'17853615598'],
                6=>['n'=>'马翠玲','p'=>'15169481355'],
                7=>['n'=>'李世涛','p'=>'13869642124'],
                8=>['n'=>'张京东','p'=>'15062642234'],
                9=>['n'=>'胡秀芬','p'=>'13188868766'],
                10=>['n'=>'盛显岐','p'=>'15065608499'],
                11=>['n'=>'黄敏光','p'=>'13978597885'],
                12=>['n'=>'郭显萍','p'=>'13394497808'],
                13=>['n'=>'张佐良','p'=>'13853689899'],
                14=>['n'=>'刘志波','p'=>'15065601687'],
                15=>['n'=>'刘苪绮妈妈','p'=>'13964645479'],
                16=>['n'=>'朱文江','p'=>'13058724777'],
                17=>['n'=>'韩燕','p'=>'15953609416'],
                18=>['n'=>'王秀玉','p'=>'15169634068'],
                19=>['n'=>'盛显峰','p'=>'15095168858'],
                20=>['n'=>'孙莉媛','p'=>'15094938662'],
                21=>['n'=>'窦燕丽','p'=>'15264630102'],
                22=>['n'=>'富霞','p'=>'15148247498'],
                23=>['n'=>'崔君','p'=>'15253638132'],
                24=>['n'=>'李华翠','p'=>'18706515598'],
                25=>['n'=>'王凯','p'=>'13695493881'],
                26=>['n'=>'贾春梅','p'=>'15096640908'],
                27=>['n'=>'西梓娴','p'=>'15763080333'],
                29=>['n'=>'冯相玲','p'=>'13606460608'],
                30=>['n'=>'段文磊','p'=>'15053661635'],
                31=>['n'=>'司志峰','p'=>'18653350672']
                ];*/
        $data = [
                0=>['n'=>'马明凤','p'=>'15064693739']
                ];        
        foreach ($data as $k => $v) {
            $in['nickname'] = $in['truename'] =  $v['n'];
            $in['phone'] = $v['p'];
            $in['created_at'] = $in['updated_at'] = time();
            $in['prestore'] = 1200;
            $in['class'] = 4;
            $in['headimgurl'] = 'http://youlianjingxuan.com/uploads/20170802/logo.png';
            $in['password'] = $in['two_password'] = md5('123456');
            $res =  db('users')->insertGetId($in);
            echo $res.'<br/>';
        }
    }
    public function ztj(){
        $data = [
            8=>['n'=>'AA','p'=>'15063632296']
           /* 9=>['n'=>'胡秀芬','p'=>'13188868766'],
            24=>['n'=>'李华翠','p'=>'18706515598'],
            25=>['n'=>'王凯','p'=>'13695493881'],
            26=>['n'=>'贾春梅','p'=>'15069640908'],
            27=>['n'=>'西梓娴','p'=>'15763080333'],
            31=>['n'=>'司志峰','p'=>'18653350672'],
            10=>['n'=>'盛显岐','p'=>'15065608499'],
            21=>['n'=>'窦燕丽','p'=>'15264630102'],
            14=>['n'=>'刘志波','p'=>'15065601687'],
            3=>['n'=>'齐君','p'=>'14768602625'],
            2=>['n'=>'孙庆刚','p'=>'15863269000'],
            10=>['n'=>'雨泽','p'=>'13562677906']*/       
        ];
        $in['uid'] =19;
        $res = [];
        foreach ($data as $key => $value) {
            $in['from_uid'] = db('users')->where(['phone'=>$value['p']])->value('id');
            $in['balance'] = 100;
            $in['create_at'] = time();
            $in['type'] = 11;
            $in['remark'] = '老系统补返直推奖';
            $in['score'] = 0;
            $res[$key]['id'] = $id = db('account')->insertGetId($in);
            $res[$key]['fx'] = $req = db('users')->where(['id'=>19])->setInc('balance',100);
        }
        dump($res);


    }
    
/*$data = [
                0=>['n'=>'齐艳红','p'=>'15169412099'],
                1=>['n'=>'齐艳红','p'=>'13563694839'],
                2=>['n'=>'孙庆刚','p'=>'15863269000'],
                3=>['n'=>'齐君','p'=>'14768602625'],
                4=>['n'=>'王新燕','p'=>'13791853868'],
                5=>['n'=>'张硕','p'=>'17853615598'],
                6=>['n'=>'马翠玲','p'=>'15169481355'],
                7=>['n'=>'李世涛','p'=>'13869642124'],
                8=>['n'=>'张京东','p'=>'15062642234'],
                9=>['n'=>'胡秀芬','p'=>'13188868766'],
                10=>['n'=>'盛显岐','p'=>'15065608499'],
                11=>['n'=>'黄敏光','p'=>'13978597885'],
                12=>['n'=>'郭显萍','p'=>'13394497808'],
                13=>['n'=>'张佐良','p'=>'13853689899'],
                14=>['n'=>'刘志波','p'=>'15065601687'],
                15=>['n'=>'刘苪绮妈妈','p'=>'13964645479'],
                16=>['n'=>'朱文江','p'=>'13058724777'],
                17=>['n'=>'韩燕','p'=>'15953609416'],
                18=>['n'=>'王秀玉','p'=>'15169634068'],
                19=>['n'=>'盛显峰','p'=>'15095168858'],
                20=>['n'=>'孙莉媛','p'=>'15094938662'],
                21=>['n'=>'窦燕丽','p'=>'15264630102'],
                22=>['n'=>'富霞','p'=>'15148247498'],
                23=>['n'=>'崔君','p'=>'15253638132'],
                24=>['n'=>'李华翠','p'=>'18706515598'],
                25=>['n'=>'王凯','p'=>'13695493881'],
                26=>['n'=>'贾春梅','p'=>'15096640908'],
                27=>['n'=>'西梓娴','p'=>'15763080333'],
                29=>['n'=>'冯相玲','p'=>'13606460608'],
                30=>['n'=>'段文磊','p'=>'15053661635'],
                31=>['n'=>'司志峰','p'=>'18653350672']
                ];*/
}