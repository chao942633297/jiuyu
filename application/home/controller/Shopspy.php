<?php 
namespace app\home\controller;

use app\backsystem\model\UserModel;
use app\backsystem\model\ShopGoodsModel;
use app\backsystem\model\ShopSpyRecordModel;
use think\Controller;
use think\Validate;
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
	}


	// 展示窥探商品列表 
	public function shopGoodsList()
	{
		$shopgoods = new ShopGoodsModel();
		//根据sort 获取销售中（is_under=0）的窥探商品
		$page = !empty(input('param.page')) ? input('param.page') : '1';
		$limit = !empty(input('param.limit')) ? input('param.limit') : '10';
		$offset = ($page-1)*$limit;
		$where = array();
		$where['is_under'] = '0';
		$where['cid'] = '2'; //只获取窥探商品 还没有过获奖间隔期的商品

		// 返回列表之前先更新需要重新上架的窥探商品
		if (db('shop_spy_goods')->where(['sur_price'=>'0'])->count()) {
			# code...
		}

		$shopGoodsList = $shopgoods->getShopGoodsByWhere($where, $offset, $limit,'sort desc,id desc','id,name,cid,unit,imgurl,remark,description,canshu,once_price,int_time,countdown,hot');
		
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
		    'two_password'=>'require',
		];
		$msg = [
		    'goodsid.require'=>'商品id不能空',
		    'spy_num'=>'窥探次数不能空',
		    'payment'=>'支付方式不能空',
		    'two_password'=>'支付密码不能空',
		];

		$_POST['two_password'] = '123456'; 
		$_POST['goodsid'] = '45'; 
		$_POST['spy_num'] = '3'; 
		$_POST['payment'] = '3'; 
		
		$input = input('post.');
		$validate = new Validate($rule,$msg);
		if(!$validate->check($input)){
		    return json(['msg'=>$validate->getError(),'code'=>0]);
		}
		if ($input['spy_num'] < 1 || $input['spy_num'] > 10) {
			return json(['code'=>0,'data'=>'','msg'=>'窥探次数必须是1-10次']);
		}

		// 验证支付密码
		$user = UserModel::get($this->userId);
		if($user['two_password'] !== md5($input['two_password'])){
		    return json(['msg'=>'支付密码不正确','code'=>0,'data'=>'']);
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


		//检查 本轮次 抢购中的订单 是否倒计时已过完 过完这表示中奖 不能再窥探
		// if (db('shop_spying_goods')->where('goodsid'=>$input['goodsid'],'times'=>$goodsInfo['times'],'status'=>'1'])->count()) {
		// 	$exdata = db('shop_spying_goods')->where('goodsid'=>$input['goodsid'],'times'=>$goodsInfo['times'],'status'=>'1'])->find();
		// 	if ((time()-strtotime($exdata['created_at']))/3600 > $goodsInfo['countdown']) {
		// 		// 将本订单更新为status=3  
				
		// 		// 重置商品 信息  last_wintime  sur_price 等信息
				
		// 		return json(['code'=>0,'data'=>'','msg'=>'商品已经被别人抢走了']);	
		// 	}
		// }

		$insertData['userid'] = $this->userId;
		$insertData['username'] = $user['nickname'];
		$insertData['once_price'] = $goodsInfo['once_price'];
		$insertData['spy_num'] = $input['spy_num'];
		$insertData['amount'] = $insertData['once_price']*$insertData['spy_num'];
		$insertData['goodsid'] = $goodsInfo['id'];
		$insertData['goodsname'] = $goodsInfo['name'];
		$insertData['goodsimgurl'] = $goodsInfo['imgurl'];
		$insertData['payment'] = $input['payment'];

		//检查自己本轮次是否在抢购中  抢购中的用户 不能窥探 
		if (db('shop_spying_goods')->where(['userid'=>$this->userId,'goodsid'=>$input['goodsid'],'times'=>$goodsInfo['times'],'status'=>'1'])->count()) {
			return json(['code'=>0,'data'=>'','msg'=>'您已经抢购了此商品，不能窥探！']);	
		}


		//余额支付 
		$ShopSpy = new ShopSpyRecordModel();
		$lastRecord =  $ShopSpy->getLastSpyRecords($this->userId,$goodsInfo['id']);
		$lastRecord = objToArray($lastRecord);

		if (!empty($lastRecord) && time()-strtotime($lastRecord[0]['created_at']) < $goodsInfo['int_time']) {
			return json(['code'=>0, 'data'=>'', 'msg'=>"窥探间隔时间".$goodsInfo['int_time']."秒，请稍后重试"]);
		}
		// dump($lastRecord);
		// dump($lastRecord[0]['created_at']);
		// exit;
		if ($input['payment'] == 3) {
			$flag = $ShopSpy->addShopSpyRecord($insertData);		
		}

		return json(['code'=>$flag['code'], 'data'=>$flag['data'], 'msg'=>$flag['msg']]);

		// dump($insertData);
        // $ShopOrder = new ShopOrderModel();
        // $flag = $ShopOrder->addShopOrder($insertData);
        // return json([$flag['code'], $flag['data'], $flag['msg']]);
	}



	/** 抢购支付 并未产生订单
	 *
	 *
	 *
	 */
	public function panicPay()
	{
		$rule = [
		    'goodsid'=>'require',
		    'payment'=>'require',
		    'two_password'=>'require',
		    'province'=>'require',
		    'city'=>'require',
		    'area'=>'require',
		    'buyer_name'=>'require',
		    'buyer_phone'=>'require',
		];
		$msg = [
		    'goodsid.require'=>'商品id不能空',
		    'payment'=>'支付方式不能空',
		    'two_password'=>'支付密码不能空',
		    'province'=>'请填写省',
		    'city'=>'请填写市',
		    'area'=>'请填写详细地址',
		    'buyer_name'=>'请填写收货人姓名',
		    'buyer_phone'=>'请填写收货人联系电话',
		];

		$_POST['two_password'] = '123456'; 
		$_POST['goodsid'] = '45'; 
		$_POST['payment'] = '3'; 
		$_POST['province'] = '河南省'; 
		$_POST['city'] = '郑州市'; 
		$_POST['area'] = '高新区'; 
		$_POST['buyer_name'] = '王先生'; 
		$_POST['buyer_phone'] = '18623695469'; 
		
		$input = input('post.');
		$validate = new Validate($rule,$msg);
		if(!$validate->check($input)){
		    return json(['msg'=>$validate->getError(),'code'=>0]);
		}

		// 验证支付密码
		$user = UserModel::get($this->userId);
		if($user['two_password'] !== md5($input['two_password'])){
		    return json(['msg'=>'支付密码不正确','code'=>0,'data'=>'']);
		}
		

		$goodsInfo = db('shop_goods')->where('id',$input['goodsid'])->field('*')->find();
		
		if ($goodsInfo['is_under'] == '1' || empty($goodsInfo)) {
			return json(['code'=>0,'data'=>'','msg'=>$goodsInfo['name'].'商品已经下架,请选择其他商品抢购！']);
		}
		if ($goodsInfo['sur_price'] == '0') {
			return json(['code'=>0,'data'=>'','msg'=>$goodsInfo['name'].'商品已经被别人窥探走了,请选择其他商品抢购！']);
		}
		if (!in_array($input['payment'],array('1','2','3'))) {
			return json(['code'=>0,'data'=>'','msg'=>'支付方式错误']);
		}

		$insertData['spy_sn'] = $this->getSpySn();  //生成抢购单号
		$insertData['userid'] = $this->userId;
		$insertData['username'] = $user['nickname'];
		$insertData['sur_price'] = $goodsInfo['sur_price'];
		$insertData['times'] = $goodsInfo['times'];
		$insertData['goodsid'] = $input['goodsid'];
		$insertData['goodsname'] = $goodsInfo['name'];
		$insertData['goodsimgurl'] = $goodsInfo['imgurl'];
		$insertData['payment'] = $input['payment'];
		$insertData['buyer_name'] = $input['buyer_name'];
		$insertData['buyer_phone'] = $input['buyer_phone'];
		$insertData['province'] = $input['province'];
		$insertData['city'] = $input['city'];
		$insertData['area'] = $input['area'];

		//检查 本轮次 抢购中的订单 是否倒计时已过完 过完这表示中奖
		if (db('shop_spying_goods')->where(['goodsid'=>$input['goodsid'],'times'=>$goodsInfo['times'],'status'=>'1'])->count()) {
			$exdata = db('shop_spying_goods')->where(['goodsid'=>$input['goodsid'],'times'=>$goodsInfo['times'],'status'=>'1'])->find();
			if ((time()-strtotime($exdata['created_at']))/3600 > $goodsInfo['countdown']) {
				// 将本订单更新为status=3  
				db('shop_spying_goods')->where(['id'=>$exdata['id']])->setField('status','3');

				// 重置商品 信息  last_wintime  sur_price
				db('shop_goods')->where(['id'=>$input['goodsid']])->setInc('spy_amount',$exdata['sur_price']);				
				db('shop_goods')->where(['id'=>$input['goodsid']])->update(['sur_price'=>'0','last_wintime'=>time(),]);				
				
				return json(['code'=>0,'data'=>'','msg'=>'商品已经被别人抢走了']);	
			}
		}

		//检查自己本轮次是否在抢购中 防止重复生成抢购订单
		if (db('shop_spying_goods')->where(['userid'=>$this->userId,'goodsid'=>$input['goodsid'],'times'=>$goodsInfo['times'],'status'=>'1'])->count()) {
			return json(['code'=>0,'data'=>'','msg'=>'订单已经生成，请勿重复下单']);	
		}
		//余额支付 
		// dump($lastRecord);
		// dump($lastRecord[0]['created_at']);
		// exit;

		$ShopSpy = new ShopSpyRecordModel();
		if ($input['payment'] == 3) {
			$flag = $ShopSpy->addShopSpying($insertData);		
		}

		return json(['code'=>$flag['code'], 'data'=>$flag['data'], 'msg'=>$flag['msg']]);

		// dump($insertData);
        // $ShopOrder = new ShopOrderModel();
        // $flag = $ShopOrder->addShopOrder($insertData);
        // return json([$flag['code'], $flag['data'], $flag['msg']]);
	}





		/**
		 * 随机生成 抢购号
		 */
	 	public function getSpySn(){
		    do{
		        $num = date('Y').date('m').date('d').time().rand(1000,9999);
		    }while(db('shop_spying_goods')->where(['spy_sn'=>$num])->find());
		    return $num;
		}


}