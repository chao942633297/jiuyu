<?php 
namespace app\home\controller;

use think\Controller;
use think\Validate;
use app\backsystem\model\ShopGoodsModel;
use app\backsystem\model\ShopSpyRecordModel;
/**
* 窥探商品
*/
class Shopspy extends Controller
{
	protected $userId;

	public function _initialize()
	{
	    parent::_initialize(); // 判断用户是否登陆
	    session('home_user_id','90');
	    $this->userId = session('home_user_id');
	    // $this->userId = input('param.userId');
	    if ($this->userId < 0 || empty($this->userId)) {
	    	return json(['code'=>0,'data'=>'','msg'=>'获取用户信息失败']);
	    }
	}


	// 展示窥探商品列表 
	public function shopGoodsList()
	{
		$shopgoods = new ShopGoodsModel();
		//根据sort 获取销售中（is_under=0）的窥探商品
		$page = !empty(input('param.page')) ? input('param.page') : '1';
		$limit = !empty(input('param.limit')) ? input('param.limit') : '10';
		$cid = !empty(input('param.cid')) ? input('param.cid') : '';  //分类ID
		$offset = ($page-1)*$limit;
		$where = array();
		$where['is_under'] = '0';
		$where['cid'] = '3'; //只获取窥探商品
		// $where['cid'] = '3'; //只获取窥探商品  还没有过获奖间隔期的商品
		if (!empty($cid)) {
			$where['cid'] = $cid;
		}
		$shopGoodsList  = $shopgoods->getShopGoodsByWhere($where, $offset, $limit,'sort desc,id desc','id,name,cid,unit,imgurl,remark,description,canshu,once_price,int_time,countdown,hot');
		
		return json(['code'=>1,'data'=>$shopGoodsList,'msg'=>'success']);

	}


	/*获取窥探商品详情
	 * @param id 商品id
	 *
	 */
	public function shopGoodsInfo()
	{
		$id = !empty(input('id')) && input('id') > 0 ? input('id') : exit(json_encode(['code'=>0,'data'=>'','msg'=>'参数异常']));
		$shopgoods = new ShopGoodsModel();
		$goodsInfo = $shopgoods->getOneShopGoods($id,"id,name,cid,unit,imgurl,remark,description,canshu,once_price,int_time,countdown,hot");
		return json(['code'=>1,'data'=>$goodsInfo,'msg'=>'success']);
	}


	/** 窥探支付
	 *
	 *
	 *
	 */
	public function spyadd()
	{
		$rule = [
		    'goodsid'=>'require',
		    'spy_num'=>'require',
		    'payment'=>'require',
		];
		$msg = [
		    'goodsid.require'=>'商品id不能空',
		    'spy_num'=>'窥探次数不能空',
		    'payment'=>'支付方式不能空',
		];

		$_POST['goodsid'] = '43'; 
		$_POST['spy_num'] = '5'; 
		$_POST['payment'] = '3'; 
		
		$input = input('post.');
		$validate = new Validate($rule,$msg);
		if(!$validate->check($input)){
		    return json(['msg'=>$validate->getError(),'code'=>0]);
		}
		if ($input['spy_num'] < 1 || $input['spy_num'] > 10) {
			return json(['code'=>0,'data'=>'','msg'=>'窥探次数必须是1-10次']);
		}
		

		$goodsInfo = db('shop_goods')->where('id',$input['goodsid'])->field('*')->find();
		
		if ($goodsInfo['is_under'] == '1' || empty($goodsInfo)) {
			return json(['code'=>0,'data'=>'','msg'=>$goodsInfo['name'].'商品已经下架,请选择其他商品窥探！']);
		}
		if ($goodsInfo['sur_price'] == '0') {
			return json(['code'=>0,'data'=>'','msg'=>$goodsInfo['name'].'商品已经被别人窥探走了,请选择其他商品窥探！']);
		}
		if (!in_array($input['payment'],array('1','2','3'))) {
			return json(['code'=>0,'data'=>'','msg'=>'支付方式错误']);
		}

		$insertData['userid'] = $this->userId;
		$insertData['once_price'] = $goodsInfo['once_price'];
		$insertData['spy_num'] = $input['spy_num'];
		$insertData['amount'] = $insertData['once_price']*$insertData['spy_num'];
		$insertData['goodsid'] = $goodsInfo['id'];
		$insertData['goodsname'] = $goodsInfo['name'];
		$insertData['goodsimgurl'] = $goodsInfo['imgurl'];

		//余额支付 
		if ($input['payment'] == 3) {
			$ShopSpy = new ShopSpyRecordModel();
			$flag = $ShopSpy->addShopSpyRecord($insertData);		
		}

		return json([$flag['code'], $flag['data'], $flag['msg']]);

		// dump($insertData);
        // $ShopOrder = new ShopOrderModel();
        // $flag = $ShopOrder->addShopOrder($insertData);
        // return json([$flag['code'], $flag['data'], $flag['msg']]);
	}


}