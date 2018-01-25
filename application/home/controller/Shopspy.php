<?php 
namespace app\home\controller;

use app\backsystem\model\UserModel;
use app\backsystem\model\ShopGoodsModel;
use app\backsystem\model\ShopSpyRecordModel;
use think\Controller;
use think\Validate;
use think\Session;
use think\Db;
use app\home\Alipay;
/**
* 窥探商品
*/
class Shopspy extends Controller
{


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
		$where['is_delete'] = '0';
		$where['cid'] = '2'; //只获取窥探商品 
		$where['status'] = '0'; //0:正常 1：中奖间隔期

		// 返回列表之前先检测和更新需要重新上架的窥探商品
		if (Db::name('shop_goods')->where(['sur_price'=>'0'])->count()) {
			$reData = Db::name('shop_goods')->where(['sur_price'=>'0'])->select();
			foreach ($reData as $key => $value) {
				if ((time()-strtotime($value['last_wintime']))/3600  > $value['she_timeint']) {
					$setData = [];
					$setData['sur_price'] = $value['price'];
					$setData['spy_price'] = '0';
					$setData['status'] = '0';
					// $setData['times'] = $value['times']+1;  // 中奖成功后 轮次已经加1 此处不用再加
					Db::name('shop_goods')->where('id',$value['id'])->update($setData);
				}
			}
		}

		$shopGoodsList = $shopgoods->getShopGoodsByWhere($where, $offset, $limit,'sort desc,id desc','id,name,cid,unit,imgurl,remark,description,canshu,once_price,int_time,countdown,hot');
		
		return json(['code'=>1,'data'=>$shopGoodsList,'msg'=>'success']);

	}

	//全部中奖列表
	public function wonlist()
	{
		$page = !empty(input('param.page')) ? input('param.page') : '1';
		$limit = !empty(input('param.limit')) ? input('param.limit') : '10';
		$offset = ($page-1)*$limit;
		$listData = Db::name('shop_spy_success')->field("id,username,goodsname,goodsid,created_at")->limit($offset,$limit)->order("id DESC")->select();
		return json(['code'=>1,'data'=>$listData,'msg'=>'success']);
	}

	//个人中奖记录
	public function myWonlist()
	{
		$userid = session('home_user_id');
		if (empty($userid) || ($userid < 0)) {
			return json(['code'=>0,'data'=>'','msg'=>'请先登录！']);
		}

		$page = !empty(input('param.page')) ? input('param.page') : '1';
		$limit = !empty(input('param.limit')) ? input('param.limit') : '10';
		$offset = ($page-1)*$limit;
		$listData = Db::name('shop_spy_success')->field("id,username,goodsname,goodsimgurl,sur_price,goodsid,created_at")->where(['userid'=>$userid])->limit($offset,$limit)->order("id DESC")->select();
		return json(['code'=>1,'data'=>$listData,'msg'=>'success']);
	}

	/*获取窥探商品详情
	 * @param id 商品id
	 *
	 */
	public function shopGoodsInfo()
	{
		$id = !empty(input('param.id')) && input('param.id') > 0 ? input('param.id') : exit(json_encode(['code'=>0,'data'=>'','msg'=>'参数异常']));
		$goodsInfo = Db::name('shop_goods')->where('id',$id)->field("id,name,cid,unit,imgurl,remark,description,canshu,once_price,int_time,countdown,hot")->find();
		if ($goodsInfo['cid'] != '2') {
			return json(['code'=>0,'data'=>'','msg'=>'非窥探商品不予显示']);
		}

		if (empty(session('home_user_id'))) {
			$goodsInfo['balance'] = '0.00';
		}else{
			$user = UserModel::get(session('home_user_id'));
			$goodsInfo['balance'] = $user->balance;
		}
		// $shopgoods = new ShopGoodsModel();
		// $goodsInfo = $shopgoods->getOneShopGoods($id,"id,name,cid,unit,imgurl,remark,description,canshu,once_price,int_time,countdown,hot");
		return json(['code'=>1,'data'=>$goodsInfo,'msg'=>'success']);
	}


	/** 窥探支付 
	 *
	 *
	 *
	 */
	public function spyadd()
	{
		$userid = session('home_user_id');
		if (empty($userid) || ($userid < 0)) {
			return json(['code'=>0,'data'=>'','msg'=>'请先登录！']);
		}

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

		// $_POST['two_password'] = '123456'; 
		// $_POST['goodsid'] = '45'; 
		// $_POST['spy_num'] = '6'; 
		// $_POST['payment'] = '1'; 
		
		$input = input('post.');
		$validate = new Validate($rule,$msg);
		if(!$validate->check($input)){
		    return json(['msg'=>$validate->getError(),'code'=>0]);
		}
		if ($input['spy_num'] < 1 || $input['spy_num'] > 10) {
			return json(['code'=>0,'data'=>'','msg'=>'窥探次数必须是1-10次之间']);
		}

		// 验证支付密码
		$user = UserModel::get($userid);
		if($user['two_password'] !== md5($input['two_password'])){
		    return json(['msg'=>'支付密码不正确','code'=>0,'data'=>'']);
		}
		

		$goodsInfo = Db::name('shop_goods')->where('id',$input['goodsid'])->field('*')->find();
		
		if ($goodsInfo['is_under'] == '1' || empty($goodsInfo)) {
			return json(['code'=>0,'data'=>'','msg'=>$goodsInfo['name'].'商品已经下架,请选择其他商品窥探！']);
		}
		if ($goodsInfo['sur_price'] == '0') {
			return json(['code'=>0,'data'=>'','msg'=>$goodsInfo['name'].'商品已经被别人窥探走了,请选择其他商品窥探！']);
		}
		if (!in_array($input['payment'],array('1','2','3'))) {
			return json(['code'=>0,'data'=>'','msg'=>'支付方式错误']);
		}


		//检查 本轮次 抢购中的订单 是否倒计时已过完 过完这表示中奖
		$r = $this->checkSpy($input['goodsid']);
		if ($r == 1) {
			return json(['code'=>0,'data'=>'','msg'=>'商品已经被别人抢走了']);	
		}

		if (($input['payment'] == 3) && $user->balance < ($goodsInfo['once_price']*$input['spy_num'])) {
			return json(['code'=>0,'data'=>'','msg'=>'余额不足']);		
		}
		//检查自己本轮次是否在抢购中  抢购中的用户 不能窥探 
		if (Db::name('shop_spying_goods')->where(['userid'=>$userid,'goodsid'=>$input['goodsid'],'times'=>$goodsInfo['times'],'status'=>'1'])->count()) {
			return json(['code'=>0,'data'=>'','msg'=>'您已经抢购了此商品，不能继续窥探！']);	
		}

		$insertData['userid'] = $userid;
		$insertData['username'] = $user['nickname'];
		$insertData['once_price'] = $goodsInfo['once_price'];
		$insertData['spy_num'] = $input['spy_num'];
		$insertData['amount'] = $insertData['once_price']*$insertData['spy_num'];
		$insertData['sur_price'] = $goodsInfo['sur_price']-$insertData['amount']; // 窥探后剩余价格
		$insertData['goodsid'] = $goodsInfo['id'];
		$insertData['goodsname'] = $goodsInfo['name'];
		$insertData['goodsimgurl'] = $goodsInfo['imgurl'];
		$insertData['times'] = $goodsInfo['times'];
		$insertData['payment'] = $input['payment'];
		$insertData['status'] = '1';
		$insertData['spy_sn'] = $this->getSpyRecordSn();


		//余额支付 
		$ShopSpy = new ShopSpyRecordModel();
		$lastRecord =  $ShopSpy->getLastSpyRecords($userid,$goodsInfo['id']);
		$lastRecord = objToArray($lastRecord);

		if (!empty($lastRecord) && time()-strtotime($lastRecord[0]['created_at']) < $goodsInfo['int_time']) {
			return json(['code'=>0, 'data'=>'', 'msg'=>"窥探间隔时间".$goodsInfo['int_time']."秒，请稍后重试"]);
		}


		// dump($lastRecord);
		// dump($lastRecord[0]['created_at']);
		// exit;
		if ($input['payment'] == 3) {
			$flag = $ShopSpy->addShopSpyRecord($insertData);		
		}else if ($input['payment'] == 1) {
			$id = Db::name('shop_spy_record')->insertGetid($insertData);
			if ($id) {
				$url = config('back_domain').'/home/alipay/webSpyPay?orderId='.$id;
				return json(['msg'=>'','code'=>1,'data'=>$url]);
			}else{
				return json(['msg'=>'发起支付失败','code'=>0,'data'=>$url]);
			}
		}else if ($input['payment'] == 2) {
			$id = Db::name('shop_spy_record')->insertGetid($insertData);
			if ($id) {
				$url = config('back_domain').'/home/alipay/webSpyPay?orderId='.$id;
				return json(['msg'=>'','code'=>1,'data'=>$url]);
			}else{
				return json(['msg'=>'发起支付失败','code'=>0,'data'=>$url]);
			}
		}else{
			return json(['code'=>0, 'data'=>'', 'msg'=>'支付方式错误']);
		}

		return json(['code'=>$flag['code'], 'data'=>$flag['data'], 'msg'=>$flag['msg']]);

	}



	/** 抢购支付 并未产生订单
	 *
	 *
	 *
	 */
	public function panicPay()
	{
		$userid = session('home_user_id');
		if (empty($userid) || ($userid < 0)) {
			return json(['code'=>0,'data'=>'','msg'=>'请先登录！']);
		}
		$rule = [
		    'goodsid'=>'require',
		    'payment'=>'require',
		    'two_password'=>'require',
		    'province'=>'require',
		    'city'=>'require',
		    'area'=>'require',
		    'detail'=>'require',
		    'buyer_name'=>'require',
		    'buyer_phone'=>'require',
		];
		$msg = [
		    'goodsid.require'=>'商品id不能空',
		    'payment'=>'支付方式不能空',
		    'two_password'=>'支付密码不能空',
		    'province'=>'请填写省',
		    'city'=>'请填写市',
		    'area'=>'请填写区',
		    'detail'=>'请填写详细地址',
		    'buyer_name'=>'请填写收货人姓名',
		    'buyer_phone'=>'请填写收货人联系电话',
		];

		// $_POST['two_password'] = '123456'; 
		// $_POST['goodsid'] = '46'; 
		// $_POST['payment'] = '3'; 
		// $_POST['province'] = '河南省'; 
		// $_POST['city'] = '郑州市'; 
		// $_POST['area'] = '高新区'; 
		// $_POST['detail'] = '莲花街工大36号'; 
		// $_POST['buyer_name'] = '王先生'; 
		// $_POST['buyer_phone'] = '18623695465'; 
		
		$input = input('post.');
		$validate = new Validate($rule,$msg);
		if(!$validate->check($input)){
		    return json(['msg'=>$validate->getError(),'code'=>0]);
		}

		// 验证支付密码
		$user = UserModel::get($userid);
		if($user['two_password'] !== md5($input['two_password'])){
		    return json(['msg'=>'支付密码不正确','code'=>0,'data'=>'']);
		}
		

		$goodsInfo = Db::name('shop_goods')->where('id',$input['goodsid'])->field('*')->find();
		
		if ($goodsInfo['is_under'] == '1' || empty($goodsInfo)) {
			return json(['code'=>0,'data'=>'','msg'=>$goodsInfo['name'].'商品已经下架,请选择其他商品抢购！']);
		}
		if ($goodsInfo['sur_price'] == '0') {
			return json(['code'=>0,'data'=>'','msg'=>$goodsInfo['name'].'商品已经被别人窥探走了,请选择其他商品抢购！']);
		}
		if (!in_array($input['payment'],array('1','2','3'))) {
			return json(['code'=>0,'data'=>'','msg'=>'支付方式错误']);
		}

		//检查 本轮次 抢购中的订单 是否倒计时已过完 过完这表示中奖
		$r = $this->checkSpy($input['goodsid']);
		if ($r == 1) {
			return json(['code'=>0,'data'=>'','msg'=>'商品已经被别人抢走了']);	
		}
	

		//自己查询窥探后的价格 防止抢购前别人又窥探导致价格变动
		$recordData = Db::name('shop_spy_record')->where(['goodsid'=>$input['goodsid'],'userid'=>$userid])->order('id DESC')->limit('1')->find();


		if (($input['payment'] == 3) && $user->balance < $recordData['sur_price']) {
			return json(['code'=>0,'data'=>'','msg'=>'余额不足']);		
		}

		$insertData['userid'] = $userid;
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
		$insertData['detail'] = $input['detail'];
		$insertData['spy_sn'] = $this->getSpySn();  //生成抢购单号



		//检查自己本轮次是否在抢购中 防止重复生成抢购订单
		if (Db::name('shop_spying_goods')->where(['userid'=>$userid,'goodsid'=>$input['goodsid'],'times'=>$goodsInfo['times'],'status'=>'1'])->count()) {
			return json(['code'=>0,'data'=>'','msg'=>'订单已经生成，请勿重复下单']);	
		}
		
		//余额支付 
		$ShopSpy = new ShopSpyRecordModel();
		if ($input['payment'] == 3) {
			$flag = $ShopSpy->addShopSpying($insertData);	
			$data = [];
			if ($flag['code'] == 1) {

				$data['endtime'] = date("Y-m-d H:i:s",$goodsInfo['countdown']*3600+time());
			}
			return json(['code'=>$flag['code'], 'data'=>$data, 'msg'=>$flag['msg']]);	
		}else if ($input['payment'] == 1) {
			
			$url = config('back_domain').'/home/alipay/webPay?orderId='.$orderData['id'];
			return json(['msg'=>'','code'=>1,'data'=>$url]);

		}else if ($input['payment'] == 2) {
			$url = config('back_domain').'/home/alipay/webPay?orderId='.$orderData['id'];
			return json(['msg'=>'','code'=>1,'data'=>$url]);
		}else{
			return json(['code'=>$flag['code'], 'data'=>'', 'msg'=>'支付方式错误']);
		}

		return json(['code'=>$flag['code'], 'data'=>$flag['data'], 'msg'=>$flag['msg']]);

	}

	/** 
	 * 获取 窥探商品最后一次窥探剩余价格
	 *
	 *
	 */
	public function getLast()
	{
		$userid = session('home_user_id');
		if (empty($userid) || ($userid < 0)) {
			return json(['code'=>0,'data'=>'','msg'=>'请先登录！']);
		}

		$goodsid = !empty(input('param.goodsid')) && input('param.goodsid') > 0 ? input('param.goodsid') : exit(json_encode(['code'=>0,'data'=>'','msg'=>'参数异常']));
		$goodsInfo = Db::name('shop_goods')->where('id',$goodsid)->field("id,name,cid,unit,imgurl,remark,description,canshu,once_price,int_time,countdown,hot,times")->find();
		if ($goodsInfo['cid'] != '2') {
			return json(['code'=>0,'data'=>'','msg'=>'非窥探商品不予显示']);
		}
		//本轮次 最后一次窥探价格
		$lastData = Db::name('shop_spy_record')->where(['goodsid'=>$goodsid,'userid'=>$userid,'times'=>$goodsInfo['times']])->find();
		$goodsInfo['last_price'] = !empty($lastData['sur_price']) ? $lastData['sur_price'] : "";

		//用户余额
		$user = UserModel::get($userid);
		$goodsInfo['balance'] = $user->balance;
		
		return json(['code'=>1,'data'=>$goodsInfo,'msg'=>'success']);
	}

	/*检测 抢购表中 是否有倒计时走完的订单
	 *
	 * @param goodsid 商品id
	 * @param times   轮次
	 * return 0 没有  1 存在并生成成功纪录存在success表中
	 */
	public function checkSpy($goodsid)
	{
		//检查 本轮次 抢购中的订单 是否倒计时已过完 过完这表示中奖
		Db::startTrans();
		try{
			$goodsInfo = Db::name('shop_goods')->find($goodsid);
		    if (Db::name('shop_spying_goods')->where(['goodsid'=>$goodsid,'times'=>$goodsInfo['times'],'status'=>'1'])->count()) {
		    	$exdata = Db::name('shop_spying_goods')->where(['goodsid'=>$goodsid,'times'=>$goodsInfo['times'],'status'=>'1'])->find();
		    	if ((time()-strtotime($exdata['created_at']))/3600 > $goodsInfo['countdown']) {
		    		// 将本订单更新为status=3  向spy_success插入一条数据 
		    		Db::name('shop_spying_goods')->where(['id'=>$exdata['id']])->setField('status','3');
		    		$successData = [];
		    		$successData['goodsid'] = $goodsid;
		    		$successData['goodsname'] = $goodsInfo['name'];
		    		$successData['goodsprice'] = $goodsInfo['price'];
		    		$successData['goodsimgurl'] = $goodsInfo['imgurl'];
		    		$successData['goodscanshu'] = $goodsInfo['canshu'];
		    		$successData['sur_price'] = $exdata['sur_price'];
		    		$successData['once_price'] = $goodsInfo['once_price'];
		    		$successData['is_spy'] = '0'; // 是否是窥探中奖 0:不是（抢购中奖）1：窥探中奖
		    		$successData['userid'] = $exdata['userid'];
		    		$successData['payment'] = $exdata['payment'];
		    		$successData['times'] = $goodsInfo['times'];
		    		$successData['last_amount'] = $exdata['sur_price'];
		    		$successData['created_at'] = date("Y-m-d H:i:s");
		    		Db::name('shop_spy_success')->insert($successData);

		    		// 重置商品 信息  last_wintime  sur_price
		    		Db::name('shop_goods')->where(['id'=>$goodsid])->setInc('spy_amount',$exdata['sur_price']);				
		    		Db::name('shop_goods')->where(['id'=>$goodsid])->update(['sur_price'=>'0','last_wintime'=>$successData['created_at']]);				
		    		
		    		return '1';	
		    	}
		    }else{
			    // 提交事务 没有抢购成功的记录
			    Db::commit();    
		    	return '0';
		    }
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		    return -1;
		}
		
	}


	/**
	 * 随机生成 抢购号
	 */
 	public function getSpySn(){
	    do{
	        $num = date('YmdHis').rand(1000,9999);
	    }while(Db::name('shop_spying_goods')->where(['spy_sn'=>$num])->find());
	    return $num;
	}


	/**
	 * 随机生成 抢购号
	 */
 	public function getSpyRecordSn(){
	    do{
	        $num = date('YmdHis').rand(10000,99999);
	    }while(Db::name('shop_spy_record')->where(['spy_sn'=>$num])->find());
	    return $num;
	}


}