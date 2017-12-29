<?php 
namespace app\home\controller;

use app\backsystem\controller\File;
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
		$this->userId = 1;
	}

	/**
	 * 套餐详情
	 */
	public function packageInfo(Request $request){
		$packageId = $request->param('packageId');
		if(empty($packageId)){
			return json(['msg'=>'参数错误','code'=>1001]);
		}
		$packageDetail = Db::table('sql_goods')->field('name,price,img,description')
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
		$packageDetail = Db::table('sql_goods')->field('name,price,img')
			->where('id',$packageId)->find();
		if($request->has('addrId')){
			$where['id'] = $request->param('addrId');
		}else{
			$where['uid'] = $this->userId;
		}
		$address = Db::table('sql_address')
			->field('consignee,phone,province,city,area,detail')
			->where($where)->order('is_default','desc')->find();
		return json(['data'=>['packageDetail'=>$packageDetail,'address'=>$address],'msg'=>'查询成功','code'=>200]);
	}


	/**
	 * @param Request $request
	 * 套餐订单
	 * 执行提交订单
	 * 线下支付页面
	 */
	public function actSubmit(Request $request){
		$input = $request->post();
		$packageId = $input['packageId'];
		$addrId = $input['addrId'];
		if(empty($packageId)){
			$packageId = session('home_package_id');
		}
		if(empty($packageId)){
			return json(['msg'=>'参数错误','code'=>1001]);
		}
		if(empty($addrId)){
			session('home_package_id',$packageId);
			return json(['msg'=>'收货地址不能为空','code'=>2001]);
		}
		$address = Db::table('sql_address')
			->field('id,province,city,area')
			->where('id',$addrId)->find();
		$agentId = getAgentId($address['province'],$address['city'],$address['area']);
		$qrcode = Db::table('sql_qrcode')
			->field('uid,wqcode,aqcode')
			->where('uid',$agentId)->find();
		$packageMoney = Db::table('sql_goods')
			->where('id',$packageId)->value('price');
		return json(['data'=>['qrcode'=>$qrcode,'packageMoney'=>$packageMoney],'msg'=>'查询成功','code'=>200]);
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
		$packageId = $input['packageId'];
		$addrId = $input['addrId'];
		$type = $input['type'];           //1支付宝支付 2 微信支付
		if(empty($packageId) || empty($addrId) || empty($type)){
			return json(['msg'=>'参数错误','code'=>1001]);
		}
		$count = db('voucher')->where(['uid'=>$this->userId,'status'=>1])->count();
		if($count > 0){
			return json(['msg'=>'您已提交申请,请耐心等待','code'=>1002]);
		}
		$user = UserModel::get($this->userId);
		if($user['class'] >= 2){
			return json(['msg'=>'您已经是报单中心无需重复申请','code'=>1002]);
		}
		$file = $request->file('voucher');
		$data = [];
		if(isset($file)){
			$imgurl = File::upload($file);
			$data['img'] = $imgurl->getData()['data'];
		}else{
			return json(['msg'=>'支付凭证不能为空','code'=>1001]);
		}
		//获取套餐信息
		$package = Db::table('sql_goods')
			->field('name,price,img')
			->where('id',$packageId)->find();
 		//获取收货地址,省,市,区/县
		$address = Db::table('sql_address')
			->field('id,province,city,area')
			->where('id',$addrId)->find();
		//获取报单中心id
		$prentId = getAgentId($address['province'],$address['city'],$address['area']);

		$data['uid'] = $this->userId;
		$data['actid'] = $prentId;         //激活id
		$data['money'] = $package['price'];
		$data['pay_type'] = $address['type'];        //1支付宝支付 2 微信支付
		$data['package_name'] = $package['name'];
		$data['package_price'] = $package['price'];
		$data['package_img'] = $package['img'];
		$data['package_number'] = 1;
		$data['province'] = $address['province'];
		$data['city'] = $address['city'];
		$data['area'] = $address['area'];
		$data['status'] = 1;         //申请中
		$data['created_at'] = date('YmdHis');
		$res = VoucherModel::create($data);
		if(empty($res['img'])){
			VoucherModel::get($res['id'])->delete();
			return json(['msg'=>'提交失败','code'=>1001]);
		}
		$user = Db::table('sql_users')->field('truename,phone')->where('id',$res['uid'])->find();
		return json(['data'=>$user,'msg'=>'提交成功','code'=>200]);
	}











}