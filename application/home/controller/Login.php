<?php 
namespace app\home\Controller;

use app\backsystem\model\AddressModel;
use app\backsystem\model\UserModel;
use Service\MsgCode;
use think\Controller;
use think\Db;
use think\Exception;
use think\Loader;
use think\Request;
use think\Validate;

/**
* 登陆注册
*/
class Login extends Controller
{
	/**
	* 登陆 
	* @param phone  password
	*/
	public function toLogin()
	{
		if (input('phone') == '' || input('password') == '') {
			return json(['code'=>101,'msg'=>'信息填写不完整']);
		}
		
		if (!$userinfo = db('users')->where(['phone'=>input('phone'),'password'=>md5(input('password'))])->find()) {
			return json(['code'=>102,'msg'=>'账号或密码不正确']);
        }
        if($userinfo['status'] == 2){
            return json(['code'=>102,'msg'=>'账号已被禁止登陆']);
        }
		#存储session
		session('home_user_id',$userinfo['id']);
        $_SESSION['home_user_id'] = $userinfo['id'];
		return json(['code'=>200,'msg'=>'登陆成功']);
	}


    /**
     * @param Request $request
     * @return \think\response\Json
     * 用户注册
     */
    public function actRegister(Request $request){
        $input = $request->post();
        $prentId = isset($input['prentId'])?$input['prentId']:0;
        $validate = Loader::validate('Users');
        if(!$validate->scene('register')->check($input)){
            return json(['msg'=>$validate->getError(),'code'=>1001]);
        }
/*        $code = $input['code'];
        $time = time() - 600;
        $codeData = db('code')->where(['phone'=>$input['phone'],'type'=>1,'status'=>1])->order('id','desc')->find();
        if(strtotime($codeData['created_at']) < $time ){
            return json(['msg'=>'验证码已失效,请重新获取','code'=>1010]);
        }
        //TODO:获取验证码
        if($input['phone'] != $codeData['phone'] || $code != $codeData['code']){
            return json(['msg'=>'验证码不正确','code'=>1002]);
        }*/

        Db::startTrans();
        try{
            //增加用户
            $userData = [];
//            $falg = ['password'=>foo(6),'two_password'=>rand(100000,999999)];      //获取登陆密码支付密码
            $falg = ['password'=>123456,'two_password'=>123456];      //获取登陆密码支付密码
            $userData['pid'] = $prentId;
            $userData['phone'] = $input['phone'];
            $userData['headimgurl'] = config('back_domain').'/uploads/default.png';
            $userData['nickname'] = '用户'.$input['phone'];
            $userData['password'] = md5($falg['password']);
            $userData['two_password'] = md5($falg['two_password']);
            $userData['class'] = 1;
            $userData['created_at'] = date('YmdHis');
            $res = UserModel::create($userData);
            //增加用户地址
            $addr = [];
            $addr['uid'] = $res['id'];
            $addr['province'] = $input['province'];
            $addr['city'] = $input['city'];
            $addr['area'] = $input['area'];
            $addr['created_at'] = date('YmdHis');
            AddressModel::create($addr);
            //保存用户关系
            $this->saveUserRelation($res['id'],$res['pid']);
            //TODO:发送短信,告知用户账号密码
            //修改验证码状态
       /*     $msg = new MsgCode();
            $result = $msg->sendMsg($res['phone'],4,$falg);*/
            $result = true;
            if($result){
//               db('code')->where('id',$codeData['id'])->update(['status'=>2]);
                Db::commit();
                return json(['msg'=>'注册成功','code'=>200]);
            }
        }catch(Exception $e){
            Db::rollback();
            return json(['msg'=>'注册失败','code'=>1004]);
        }
    }


    /**
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * 找回登陆密码
     */
    public function editPassword(Request $request){
        $rule = [
            'phone'=>'require|/^1[34578]\d{9}$/',
            'code'=>'require',
            'password'=>'require|alphaDash',
            'repassword'=>'require|confirm:password',

        ];
        $msg = [
            'phone.require'=>'手机号不能为空',
            'phone./^1[34578]\d{9}$/'=>'手机号格式错误',
            'code'=>'验证码不能为空',
            'password.require'=>'新密码不能为空',
            'password.alphaDash'=>'密码只能是字母、数字和下划线_及破折号-',
            'repassword.require'=>'确认密码不能为空',
            'repassword.confirm:password'=>'确认密码和新密码不一致'
        ];
        $input = $request->post();
        $validate = new Validate($rule,$msg);
        if(!$validate->check($input)){
            return json(['msg'=>$validate->getError(),'code'=>1001]);
        }
        $code = $input['code'];
        $time = time() - 600;
        $codeData = db('code')->where(['phone'=>$input['phone'],'type'=>3,'status'=>1])->order('id','desc')->find();
        if(strtotime($codeData['created_at']) < $time ){
            return json(['msg'=>'验证码已失效,请重新获取','code'=>1010]);
        }
        //TODO:获取验证码
        if($input['phone'] != $codeData['phone'] || $code != $codeData['code']){
            return json(['msg'=>'验证码不正确','code'=>1002]);
        }
        $data['password'] = md5($input['password']);
        $res = db('users')->where('phone',$input['phone'])->update($data);
        if($res){
            db('code')->where('id',$codeData['id'])->update(['status'=>2]);
            return json(['msg'=>'修改成功','code'=>200]);
        }
        return json(['msg'=>'修改失败','code'=>1004]);
    }



    //获的验证码
    public function getMsgCode(Request $request){
        $type = $request->param('type');
        $phone = $request->param('phone');
        if(empty($type)){
            return json(['msg'=>'参数错误','code'=>1001]);
        }
        $count = db('users')->where('phone',$phone)->count();
        if($type != 1){
            if($count < 1){
                return json(['msg'=>'手机号不存在','code'=>1002]);
            }
        }else{
            if($count >= 1){
                return json(['msg'=>'该手机号已注册，请登录','code'=>2000]);
            }
        }
        //限制用户10分钟只能发短信10条
        $befor = date('Y-m-d H:i:s',time()-600);
        $count = db('code')->where(['phone'=>$phone,'created_at'=>['>=',$befor],'created_at '=>['<=',date('Y-m-d H:i:s')]])->count();
        if($count >= 10){
            return json(['msg'=>'发送短信数量过多，请稍后再试','code'=>1004]);
        }else if(db('code')->where(['phone'=>$phone,'created_at'=>['like',date('Y-m-d').'%']])->count() >= 15){
            return json(['msg'=>'今天发送短信数量完毕，请明天再试','code'=>1004]);
        }

        $msg = new MsgCode();
        $result = $msg->sendMsg($phone,$type);
        if($result){
            return json(['msg'=>'发送成功','code'=>200]);
        }
        return json(['msg'=>'发送失败，请从新发送','code'=>1003]);
    }





    public function saveUserRelation($userId,$prentId){
        Db::startTrans();
            try{
            $allPrent = Db::table('sql_user_relation')
                ->field('user_id,pid,pidlay')
                ->where('user_id',$prentId)->select();
            $last = 0;
            foreach($allPrent as $key=>$val){
                $allPrent[$key]['user_id'] = $userId;
                $allPrent[$key]['pidlay'] += 1;
                $allPrent[$key]['created_at'] = date('YmdHis');
                $last += 1;
            }
            $allPrent[$last+1]['user_id'] = $userId;
            $allPrent[$last+1]['pid'] = $prentId;
            $allPrent[$last+1]['pidlay'] = 1;
            $allPrent[$last+1]['created_at'] = date('YmdHis');
            Db::table('sql_user_relation')->insertAll($allPrent);
            Db::commit();
            return true;
        }catch(Exception $e){
            Db::rollback();
            return false;
        }
    }






	
}