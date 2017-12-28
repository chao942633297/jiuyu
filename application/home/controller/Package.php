<?php 
namespace app\home\controller;

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
		$packageDetail = Db::table('sql_goods')->field('name,price,img,description')
			->where('id',$packageId)->find();
		return json(['data'=>$packageDetail,'msg'=>'查询成功','code'=>200]);
	}


	/**
	 * 提交订单页面
	 */
	public function webSubmit(Request $request){
		$packageId = $request->param('packageId');
		$packageNum = $request->param('packageNum');
		if(empty($packageId)){
			$packageId = session('home_package_id');
		}else{
			session('home_package_id',$packageId);
		}
		if(empty($packageId) || empty($packageNum)){
			return json(['msg'=>'参数错误','code'=>1001]);
		}
		$packageDetail = Db::table('sql_goods')->field('name,price,img')
			->where('id',$packageId)->find();
		$packageDetail['number'] = $packageNum;
		$packageDetail['totalMoney'] = $packageDetail['price'] * $packageNum;
		if($request->has('addrId')){
			$where['id'] = $request->param('addrId');
		}else{
			$where['uid'] = $this->userId;
		}
		$address = Db::table('sql_address')->where($where)->order('is_default','desc')->find();
		return json(['data'=>['packageDetail'=>$packageDetail,'address'=>$address],'msg'=>'查询成功','code'=>200]);
	}



	public function actSubmit(Request $request){
		$input = $request->post();


	}


	/**
	 * 匹配报单中心
	 */
	public function machCenter($address){
		$prentId = 1;
		if($areaId = \app\backsystem\model\ApplyModel::get(['province'=>$address['province'],'city'=>$address['city'],'area'=>$address['area'],'status'=>2,'level'=>3])){
			$prentId = $areaId;
		}else if($cityId = \app\backsystem\model\ApplyModel::get(['province'=>$province,'city'=>$city,'status'=>2,'level'=>4])['uid']){
			$prentId = $cityId;
		}else if($provinceId = \app\backsystem\model\ApplyModel::get(['province'=>$province,'status'=>2,'level'=>5])['uid']){
			$prentId = $provinceId;
		}
		return $prentId;
	}





}