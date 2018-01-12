<?php 

namespace app\home\controller;
use app\backsystem\model\ShopOrderModel;
use app\backsystem\model\UserModel;
use app\backsystem\model\AccountModel;
use app\backsystem\model\ShopGoodsModel;
use think\Controller;
use think\Request;
use think\Db;
use think\Model;
use think\Validate;



class Shoporder extends Base
{
	protected $userId;

	public function _initialize()
	{
	    parent::_initialize(); // 判断用户是否登陆
	    $this->userId = session('home_user_id');
	    // $this->userId = input('post.userId');
	    if ($this->userId < 0 || empty($this->userId)) {
	    	return json(['code'=>0,'data'=>'','msg'=>'获取用户信息失败']);
	    	exit;
	    }
	}



	//订单列表
	/*
	 *
	 * @param limit 每页显示数量 默认10条
	 * @param page 页码 
	 * @param status 订单状态 
	 * 
	 */
	public function orderList()
	{
		//未支付的订单 详情重新获取商品信息 防止未付款期间价格变动

		//已经支付的订单 商品信息从order_detail 中获取  防止购买后商品价格变动造成总价对不上或者商品被删除

		//加分页
		$page = !empty(input('post.page')) && input('post.page') > 0 ? input('post.page') : '1' ;
		$limit = !empty(input('post.limit')) && input('post.limit') > 0 ? input('post.limit') : '10' ;
		$status = input('post.status');

		if (empty($status)  || $status < 0 ) {
			return json(['code'=>1,'data'=>'','msg'=>'获取失败，参数异常']);
		}

		//$status = 1 
		$where = [];
		$where['is_delete'] = '0';
		$where['uid'] = $this->userId;
		$where['status'] = $status;
		$orderData = db('shop_order')->where($where)->field('order_sn,amount,status')->select();
		$ShopOrder = new ShopOrderModel();
		foreach ($orderData as $key => $value) {
			//待付款 和取消的 订单详情 里面 商品价格需要重新获取
			if ($where['status'] <= 1 ) {
				$orderData[$key]['goodsInfo'] = db('shop_order_detail')->alias('o')->join('shop_goods g','o.goodsid = g.id','RIGHT')->where('o.order_sn',$value['order_sn'])->field('o.goodsnum,g.price')->select();
				$orderData[$key]['goodsInfo'] = objToArray($orderData[$key]['goodsInfo']);

				//重新计算订单价格
				$orderData[$key]['amount'] = $ShopOrder->sumGoodsByordersn($value['order_sn']);
			}else{
				//已经付过款的商品信息 从order_detail中获取 防止商品下架或者删除获取不到
				$orderData[$key]['goodsInfo'] = db('shop_order_detail')->where('order_sn',$value['order_sn'])->field('goodsname,goodsnum,price')->select();
				$orderData[$key]['goodsInfo'] = objToArray($orderData[$key]['goodsInfo']);
			}
		}


		return json(['code'=>1,'data'=>$orderData,'msg'=>'success']);
	}


	
	// 获取各个状态的订单数量
	public function orderCount()
	{
		$data = [];
		$where = [];
		$where['is_delete'] = '0';
		$where['uid'] = $this->userId;
		$status = ['0','1','2','3','4','5'];
		foreach ($status as $key => $value) {
			$data[$key]['status'] = $where['status'] = $value;

			$data[$key]['num'] = db('shop_order')->where($where)->count();
		}
		return json(['code'=>1,'data'=>$data,'msg'=>'success']);	
	}

	/**
	 * 生成订单
	 * 请求方式 
	 * @param  商品信息 二维数组 goodsinfo array(array('goodsid'=>**,'goodsnum'=>**),array('goodsid'=>**,'goodsnum'=>**),)
	 * @param 
	 */
	public function orderAdd()
	{
		$rule = [
		    'goodsinfo'=>'require',
		    'province'=>'require',
		    'city'=>'require',
		    'area'=>'require',
		    'detail'=>'require',
		    'buyer_name'=>'require|max:25',
		    'buyer_phone'=>'require|/^1[3456789]\d{9}$/',
		];
		$msg = [
		    'goodsinfo.require'=>'商品信息不能为空',
		    'province'=>'省不能为空',
		    'city'=>'市不能为空',
		    'area'=>'地区不能为空',
		    'detail'=>'详细地址不能为空',
		    'buyer_name'=>'用户名不能为空|名称最多不能超过25个字符',
		    'buyer_phone'=>'手机号不能为空|请输入正确的手机号',
		];

		// $_POST['cartid'] = ['21','22']; 
		// $_POST['province'] = '河南省'; 
		// $_POST['city'] = '郑州市'; 
		// $_POST['area'] = '金水区'; 
		// $_POST['detail'] = '北三环中州大道963号康杰大酒店'; 
		// $_POST['buyer_name'] = '王先生'; 
		// $_POST['buyer_phone'] = '18236952689'; 
		// $_POST['goodsinfo'] = array(
		// 	//商城区
		// 	array('goodsid'=>31,'goodsnum'=>'1'),
		// 	// array('goodsid'=>35,'goodsnum'=>'3'),
		// 	array('goodsid'=>37,'goodsnum'=>'1'),	
		// ); 

		$input = input('post.');
		$validate = new Validate($rule,$msg);
		if(!$validate->check($input)){
		    return json(['msg'=>$validate->getError(),'code'=>0]);
		}

		// 购物车下单 读取购物车商品
		if (!empty($input['cartid'])) {
			$input['goodsinfo'] = [];
			$d = db('shop_cart')->where('id','in',$input['cartid'])->field('*')->select();
			foreach ($d as $key => $value) {
				$input['goodsinfo'][$key]['goodsid'] = $value['goodsid'];
				$input['goodsinfo'][$key]['goodsnum'] = $value['goodsnum'];
			}
		}



		$goodsInfo = $input['goodsinfo'];

		$cid = ''; //不同区的商品不能合并结算 套餐和商品不能合并结算
		foreach ($goodsInfo as $key => $value) {
			//查询商品库存 和 是否下架  
			$kucun = db('shop_goods')->where('id',$value['goodsid'])->field('name,num,is_under,cid')->find();
			if ($kucun['is_under'] == '1' || empty($kucun)) {
				return json(['code'=>0,'data'=>'','msg'=>$kucun['name'].'商品已经下架,请重新下单！']);
				break;
			}

			// if ($kucun['num'] < $value['goodsnum']) {
			// 	return json(['code'=>0,'data'=>'','msg'=>$kucun['name'].'商品库存不足，请重新下单！']);
			// 	break;
			// }
			if (!empty($cid) && $cid !== $kucun['cid']) {
				return json(['code'=>0,'data'=>'','msg'=>'请将窥探和商城分开下单！']);
			}
			$cid = $kucun['cid'];
			
		}

		$insertData['cartid'] = $input['cartid'];
		$insertData['goodsinfo'] = $input['goodsinfo'];
		$insertData['province'] = $input['province'];
		$insertData['city'] = $input['city'];
		$insertData['area'] = $input['area'];
		$insertData['detail'] = $input['detail'];
		$insertData['buyer_name'] = $input['buyer_name'];
		$insertData['buyer_phone'] = $input['buyer_phone'];
		$insertData['uid'] = $this->userId;
		$insertData['order_sn'] = $this->orderNum();


        $ShopOrder = new ShopOrderModel();
        $flag = $ShopOrder->addShopOrder($insertData);
        return json([$flag['code'], $flag['data'], $flag['msg']]);

	}


	/**
	 * 未支付订单 发起支付
	 * 请求方式 
	 * @param 
	 */
	public function orderPay()
	{
		$rule = [
		    'order_sn'=>'require',
		    'two_password'=>'require',
		    'payment'=>'require',
		];
		$msg = [
		    'order_sn.require'=>'订单号不能为空',
		    'two_password.require'=>'支付密码不能为空',
		    'payment.require'=>'支付方式不能为空',
		];

		// $_POST['order_sn'] = '201801121515718028447'; 
		// $_POST['two_password'] = '123456'; 
		// $_POST['payment'] = '3'; 

		$input = input('post.');
		$validate = new Validate($rule,$msg);
		if(!$validate->check($input)){
		    return json(['msg'=>$validate->getError(),'code'=>0]);
		}

		//验证支付密码是否正确
		$user = UserModel::get($this->userId);
		if($user->two_password !== md5($input['two_password'])){
		    return json(['msg'=>'支付密码不正确','code'=>0]);
		}

		//检查订单是否已经支付过，防止重复支付
		$orderData = db('shop_order')->where('order_sn',input('post.order_sn'))->find();
		if ($orderData['status'] >= 2) {
			return json(['msg'=>'订单已经支付过','code'=>0]);
		}
		
		
		// 支付方式 1:支付宝  2：微信 3：余额
		if (input('post.payment') == 3) {
			// 检查账户余额是否充足 根据订单号重新计算商品总额，防止商品价格变动产生的影响
			$ShopOrder = new ShopOrderModel();
			$sum = $ShopOrder->sumGoodsByordersn(input('post.order_sn')); // float 型
			
			if($sum > $user->balance){
			    return json(['msg'=>'余额不足','code'=>0]);
			}else if($sum <= 0){
				//订单总金额小于0 
			    return json(['msg'=>'订单金额错误','code'=>0]);
			}

			//扣除余额 修改订单状态status为2：已支付 
			Db::startTrans();
			try{
			    //扣除用户余额
			    UserModel::get($this->userId)->setDec('balance',$sum);
			    //增加用户余额消费记录
			    $user = UserModel::get($this->userId);
			    $accountData = [];
			    $accountData['uid'] = $this->userId;
			    $accountData['balance'] = $sum; //账户 消费金额
			    $accountData['remark'] = '商城订单支付';
			    $accountData['inc'] = 2;
			    $accountData['type'] = 12;  // 扣币类型 12：商城订单支付
			    $accountData['create_at'] = date('YmdHis');
			    
			    AccountModel::create($accountData);
			    //更新订单状态  更新订单 详情信息 （商品单价等）
			    db('shop_order')->where('order_sn',input('post.order_sn'))->setField(['status'=>2,'amount'=>$sum,'payment'=>3]); 

        		$goodsinfo = db('shop_order_detail')->where('order_sn',input('post.order_sn'))->select();
        		$newData = [];
        		$where = [];
        		$where['order_sn'] = input('post.order_sn');
        		foreach ($goodsinfo as $key => $value) {
        		    $newData = db('shop_goods')->where('id',$value['goodsid'])->field('name as goodsname,price,imgurl')->find();
        		    $where['goodsid'] = $value['goodsid'];
        		    db('shop_order_detail')->where($where)->update($newData);
        		    // 销量++
        		    db('shop_goods')->where('id',$value['goodsid'])->setInc('hot',$value['goodsnum']);
        		    db('shop_goods')->where('id',$value['goodsid'])->setInc('realhot',$value['goodsnum']);
        		}
			    
			    Db::commit();
			    return json(['msg'=>'支付成功','code'=>1]);
			}catch(Exception $e){
			    Db::rollback();
			    return json(['msg'=>$e->getMessage(),'code'=>0]);
			}
			
		}
			
	}

	/* 用户自主取消订单 不是删除
	 *
	 * @param $order_sn
	 *
	 */
	public function orderCancel()
	{
		$order_sn = input('post.order_sn');

		$re = db('shop_order')->where('order_sn',$order_sn)->update(['status'=>'0']);
		if ($re > 0) {
			return json(['code'=>1,'data'=>'','msg'=>'取消成功']);
		}
		return json(['code'=>0,'data'=>'','msg'=>'取消失败']);
	}



	
	/*删除订单  不是取消订单 取消订单在上面
	 *
	 * @param $order_sn
	 *
	 */
	public function orderDel()
	{
		$order_sn = input('post.order_sn');

		$re = db('shop_order')->where('order_sn',$order_sn)->update(['is_delete'=>1]);
		if ($re > 0) {
			return json(['code'=>1,'data'=>'','msg'=>'删除成功']);
		}
		return json(['code'=>0,'data'=>'','msg'=>'删除失败']);
	}





	/**
	 * 随机生成订单号
	 */
 	public function orderNum(){
	    do{
	        $num = date('Y').date('m').date('d').time().rand(100,999);
	    }while(db('shop_order')->where(['order_sn'=>$num])->find());
	    return $num;
	}




	
	
}