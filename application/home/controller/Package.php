<?php 
namespace app\home\controller;

use app\backsystem\controller\File;
use app\backsystem\model\ApplyModel;
use app\backsystem\model\UserModel;
use app\backsystem\model\VoucherModel;
use think\Controller;
use app\backsystem\model\GoodsModel;
use think\Db;
use think\Request;

/**
* 商品-车辆
*/
class Package extends Controller
{

	protected  $userId;

	public function _initialize()
	{
		$this->userId = session('home_user_id');
	}

	/**
	 * 套餐详情
	 */
	public function packageInfo(Request $request){
		$packageId = $request->param('packageId');
		if(empty($packageId)){
			return json(['msg'=>'参数错误','code'=>1001]);
		}
		$packageDetail = Db::table('sql_goods')->field('id,name,price,img,description')
			->where('id',$packageId)->find();
		return json(['data'=>$packageDetail,'msg'=>'查询成功','code'=>200]);
	}


	/**
	 * 套餐订单
	 * 提交订单页面
	 */
	public function webSubmit(Request $request){
		$packageId = $request->param('packageId');
		if(empty($packageId)){
			$packageId = session('home_package_id');
		}else{
			session('home_package_id',$packageId);
		}
		if(empty($packageId)){
			return json(['msg'=>'参数错误','code'=>1001]);
		}
		$packageDetail = Db::table('sql_goods')->field('id,name,price,img,unit')
			->where('id',$packageId)->find();
		if($request->has('addrId')){
			$where['id'] = $request->param('addrId');
		}else{
			$where['uid'] = $this->userId;
		}
		$address = Db::table('sql_address')
			->field('id,consignee,mobile,province,city,area,detail')
			->where($where)->order('is_default','desc')->find();
		return json(['data'=>['packageDetail'=>$packageDetail,'address'=>$address],'msg'=>'查询成功','code'=>200]);
	}



	/**
	 * @param Request $request
	 * @return \think\response\Json
	 * 选择服务中心
	 * addrId 收货地址id
	 */
	public function selectService(Request $request){
		$addrId = $request->param('addrId');
		if(empty($addrId)){
			return json(['msg'=>'收货地址不能为空','code'=>1001]);
		}
		$address = Db::table('sql_address')->where('id',$addrId)->find();
		$agentId = getAgentId($address['province'],$address['city'],$address['area']);
		$apply = new ApplyModel();
		$serviceData = $apply->field('id,uid,name,phone,province,city,area')
			->where(['uid'=>['in',$agentId],'status'=>2])->select();
		foreach($serviceData as $key=>$val){
			$serviceData[$key]['headimgurl'] = $val['user']['headimgurl'];
			unset($serviceData[$key]['user']);
		}
		return json(['data'=>$serviceData,'msg'=>'查询成功','code'=>200]);
	}



	/**
	 * @param Request $request
	 * 套餐订单
	 * 执行提交订单
	 * 线下支付页面
	 */
	public function actSubmit(Request $request){
		$input = $request->post();
		$packageId = isset($input['packageId'])?$input['packageId']:'';
		$addrId = isset($input['addrId'])?$input['addrId']:'';
		$agentId = isset($input['agentId'])?$input['agentId']:'';
		if(empty($packageId)){
			$packageId = session('home_package_id');
		}
		if(empty($packageId)  || empty($agentId)){
			return json(['msg'=>'参数错误','code'=>1001]);
		}
		if(empty($addrId)){
			session('home_package_id',$packageId);
			return json(['msg'=>'收货地址不能为空','code'=>2001]);
		}
		$package = Db::table('sql_goods')
			->where('id',$packageId)->find();
		$qrcode = Db::table('sql_qcode')
			->field('uid,wqcode,aqcode')
			->where('uid',$agentId)->find();
		return json(['data'=>['qrcode'=>$qrcode,'packageMoney'=>$package['price']],'msg'=>'查询成功','code'=>200]);
	}



	/**
	 * @param Request $request
	 * @return \think\response\Json
	 * 传入套餐id
	 * 地址id
	 * 支付方式
	 */
	public function payNow(Request $request){
		$input = $request->post();
		if(empty($input['packageId']) || empty($input['addrId']) || empty($input['type']) || empty($input['agentId'])){
			return json(['msg'=>'参数错误','code'=>1001]);
		}
		$packageId = $input['packageId'];
		$addrId = $input['addrId'];
		$type = $input['type'];           //1支付宝支付 2 微信支付
		$prentId = Db::table('sql_apply')->where('id',$input['agentId'])->value('uid');   //报单中心id
		//获取套餐信息
		$package = Db::table('sql_goods')
			->field('name,price,img,unit')
			->where('id',$packageId)->find();
		$count = db('voucher')->where(['uid'=>$this->userId,'status'=>1,'type'=>$package['unit']])->count();
		if($count > 0){
			return json(['msg'=>'您已提交申请,请耐心等待','code'=>1002]);
		}
		$user = UserModel::get($this->userId);
		if(strpos($user['level'],$package['unit']) !== false){
			return json(['msg'=>'您已经购买过次套餐,暂不能购买','code'=>1002]);
		}
		$file = $request->file('voucher');
		$img = '';
		if(isset($file)){
			$imgurl = File::upload($file);
			$img = $imgurl->getData()['data'];
		}else{
			return json(['msg'=>'支付凭证不能为空','code'=>1001]);
		}
 		//获取收货地址,省,市,区/县
		$address = Db::table('sql_address')
			->field('id,consignee,mobile,province,city,area,detail')
			->where('id',$addrId)->find();
		$list = VoucherModel::getVoucherData($this->userId,$prentId,$package['price'],$img,$package['unit'],$type,$package['name'],$package['price'],$package['img'],$address['consignee'],$address['mobile'],$address['province'],$address['city'],$address['area'],$address['detail']);
		$res = VoucherModel::create($list);
		if(empty($res['img'])){
			VoucherModel::get($res['id'])->delete();
			return json(['msg'=>'提交失败','code'=>1001]);
		}
		session('home_package_id',null);
		$user = Db::table('sql_users')->field('truename,phone')->where('id',$res['actid'])->find();
		return json(['data'=>$user,'msg'=>'提交成功','code'=>200]);
	}











}