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
		$status = input('param.status');

		if (($status==NULL)  || $status < 0 ) {
			return json(['code'=>1,'data'=>'','msg'=>'获取失败，参数异常']);
		}

		$where = [];
		$where['is_delete'] = '0';
		$where['uid'] = $this->userId;
		$where['status'] = $status;
		// 
		if ($status > 0) {
			$orderData = Db::name('shop_order')->where($where)->field('order_sn,amount,status')->select();
			$ShopOrder = new ShopOrderModel();
			foreach ($orderData as $key => $value) {
				//待付款 和取消的 订单详情 里面 商品价格需要重新获取
				if ($where['status'] = 1 ) {
					$orderData[$key]['goodsInfo'] = Db::name('shop_order_detail')->alias('o')->join('shop_goods g','o.goodsid = g.id','RIGHT')->where('o.order_sn',$value['order_sn'])->field('o.goodsnum,g.price,g.name,g.imgurl')->select();
					$orderData[$key]['goodsInfo'] = objToArray($orderData[$key]['goodsInfo']);

					//重新计算订单价格
					$orderData[$key]['amount'] = $ShopOrder->sumGoodsByordersn($value['order_sn']);
				}else{
					//已经付过款的商品信息 从order_detail中获取 防止商品下架或者删除获取不到
					$orderData[$key]['goodsInfo'] = Db::name('shop_order_detail')->where('order_sn',$value['order_sn'])->field('goodsname as name,goodsnum,price,imgurl')->select();
					$orderData[$key]['goodsInfo'] = objToArray($orderData[$key]['goodsInfo']);
				}
			}
		}else{
			$this->checkSpySuccess();  //检测更新抢购订单
			// 获取抢购中的订单
			$spyData = Db::name('shop_spying_goods')->where(['userid'=>$this->userId,'status'=>'1'])->field("spy_sn,goodsid,goodsname,goodsimgurl,sur_price,status,created_at")->select();
			//格式化数据格式
			$orderData = [];
			foreach ($spyData as $key => $value) {
				$goodsInfo = Db::name('shop_goods')->field('countdown')->find($value['goodsid']);
				$orderData[$key]['order_sn'] = $value['spy_sn'];
				$orderData[$key]['amount'] = $value['sur_price'];
				$orderData[$key]['status'] = '0';
				$orderData[$key]['lefttime'] = '1';  //前端参数  
				$orderData[$key]['timeout'] = '1'; //前端参数
				$orderData[$key]['endtime'] = date("Y-m-d H:i:s",strtotime($value['created_at'])+(3600*$goodsInfo['countdown'])); //结束时间
				$orderData[$key]['goodsInfo'][0]['goodsnum'] = '1';
				$orderData[$key]['goodsInfo'][0]['price'] = $value['sur_price'];
				$orderData[$key]['goodsInfo'][0]['name'] = $value['goodsname'];
				$orderData[$key]['goodsInfo'][0]['imgurl'] = $value['goodsimgurl'];
			}



		}


		return json(['code'=>1,'data'=>$orderData,'msg'=>'success']);
	}


	/**商城订单详情
	 * @param orderid  根据订单id或者订单号查询
	 * @param order_sn 
	 *
	 */
	public function orderDetail()
	{
		if (!input('?param.orderid') && !input('?param.order_sn')) {
			return json(['code'=>0,'data'=>'','msg'=>'参数异常']);
		}
		$orderid = input('param.orderid'); 
		$order_sn = input('param.order_sn'); 
		$orderInfo = [];
		if (!empty($orderid)) {
			$orderInfo = Db::name('shop_order')->where(['id'=>$orderid])->field('id,order_sn,buyer_name,buyer_phone,amount,province,city,area,detail,status,payment,waybill_no,created_at,remark')->find(); 
		}else{
			$orderInfo = Db::name('shop_order')->where(['order_sn'=>$order_sn])->field('id,order_sn,buyer_name,buyer_phone,amount,province,city,area,detail,status,payment,waybill_no,created_at,remark')->find();
		}
		$orderDetail = [];

		//一般订单
		if (!empty($orderInfo)) {
			//待付款 和取消的 订单详情 里面 商品价格需要重新获取
			if ($orderInfo['status'] <= 1 ) {
				$ShopOrder = new ShopOrderModel();
				$orderDetailList = Db::name('shop_order_detail')->where('order_sn',$orderInfo['order_sn'])->select();
				foreach ($orderDetailList as $key => $value) {
					$orderDetail[$key] = Db::name('shop_goods')->where('id',$value['goodsid'])->field("name as goodsname,price,imgurl")->find();
					$orderDetail[$key]['goodsnum'] = $value['goodsnum'];
				}
				//重新计算订单价格
				$orderInfo['amount'] = $ShopOrder->sumGoodsByordersn($orderInfo['order_sn']);
			}else{
				//已经付过款的商品信息 从order_detail中获取 防止商品下架或者删除获取不到
				$orderDetail = Db::name('shop_order_detail')->where('order_sn',$orderInfo['order_sn'])->field('goodsname,imgurl,price,goodsnum')->select();
			}
			$orderInfo['lefttime'] = '';  // 前端参数
			$orderInfo['timeout'] = ''; // 前端参数
			$orderInfo['goodsinfo'] =$orderDetail;
		}else{
			// 抢购订单
			$spyOrderInfo = Db::name('shop_spying_goods')->where(['spy_sn'=>$order_sn])->field('*')->find();
			$goodsInfo = Db::name('shop_goods')->where(['id'=>$spyOrderInfo['goodsid']])->field('countdown')->find();
			if (!empty($spyOrderInfo)) {
				$orderInfo['id'] = $spyOrderInfo['id'];
				$orderInfo['order_sn'] = $spyOrderInfo['spy_sn'];
				$orderInfo['buyer_name'] = $spyOrderInfo['buyer_name'];
				$orderInfo['buyer_phone'] = $spyOrderInfo['buyer_phone'];
				$orderInfo['amount'] = $spyOrderInfo['sur_price'];
				$orderInfo['province'] = $spyOrderInfo['province'];
				$orderInfo['city'] = $spyOrderInfo['city'];
				$orderInfo['area'] = $spyOrderInfo['area'];
				$orderInfo['detail'] = $spyOrderInfo['detail'];
				$orderInfo['status'] = '0'; //抢购中 前端传参
				$orderInfo['payment'] = $spyOrderInfo['payment'];
				$orderInfo['waybill_no'] = '';
				$orderInfo['created_at'] = $spyOrderInfo['created_at'];
				$orderInfo['remark'] = '';
				$orderInfo['lefttime'] = '';  // 前端参数
				$orderInfo['timeout'] = ''; // 前端参数
				$orderInfo['endtime'] = date("Y-m-d H:i:s",strtotime($spyOrderInfo['created_at'])+3600*$goodsInfo['countdown']);
				$orderInfo['goodsinfo'][0]['goodsname'] = $spyOrderInfo['goodsname'];
				$orderInfo['goodsinfo'][0]['imgurl'] = $spyOrderInfo['goodsimgurl'];
				$orderInfo['goodsinfo'][0]['price'] = $spyOrderInfo['sur_price'];
				$orderInfo['goodsinfo'][0]['goodsnum'] = '1';
			}else{
				return json(['code'=>1,'data'=>'','msg'=>'未查到相关订单']);			
			}
		}
		
		return json(['code'=>1,'data'=>$orderInfo,'msg'=>'success']);	
	}

	/** 抢购中的订单详情
	 * orderid 根据orderid 或者 spy_sn 
	 * spy_sn
	 *
	 */
	public function spyOrderDetail()
	{
		$_POST['orderid'] = 1;
		if (!input('?post.orderid') && !input('?post.spy_sn')) {
			return json(['code'=>0,'data'=>'','msg'=>'参数异常']);
		}
		$orderid = input('post.orderid'); 
		$spy_sn = input('post.spy_sn'); 
		$orderInfo = [];
		if (!empty($orderid)) {
			$orderInfo = Db::name('shop_spying_goods')->where(['id'=>$orderid])->field('id,spy_sn,buyer_name,buyer_phone,province,city,area,detail,status,payment,created_at,goodsid')->find(); 
		}else{
			$orderInfo = Db::name('shop_spying_goods')->where(['spy_sn'=>$spy_sn])->field('id,spy_sn,buyer_name,buyer_phone,province,city,area,detail,status,payment,created_at,goodsid')->find();
		}

		//抢购成功的商品信息 从success表中读取
		if ($orderInfo['status'] == 3) {
			$goodsInfo = Db::name('shop_spy_success')->where('spyingid',$orderInfo['id'])->field("*")->find(); 
		}else{
			$goodsInfo = Db::name('shop_goods')->where('id',$orderInfo['goodsid'])->field("name,price,imgurl,countdown")->find(); 
		}


		$orderInfo['goodsinfo'] = $goodsInfo;
		return json(['code'=>1,'data'=>$orderInfo,'msg'=>'success']);	
	}

	
	
	/** 获取各个状态的订单数量
	 *  
	 * 返回 data[0=>'抢购订单数量'，1=>'待付款'，2=>'待发货'，3=>'待收货',4=>'完成']
	 */
	public function orderCount()
	{
		$data = [];
		$where = [];
		$where['is_delete'] = '0';
		$where['uid'] = $this->userId;
		// $status = ['0','1','2','3','4','5'];   // 0:用户取消  1：待付款 2：待发货 3：已发货 4：完成 5：抢购中
		$where['status'] = '5';
		$this->checkSpySuccess();  //获取抢购订单前先检测更新一下抢购表
		$data[0] = Db::name('shop_spying_goods')->where(['userid'=>$this->userId,'status'=>'1'])->count();   //  抢购中的订单
		$where['status'] = '1';
		$data[1] = Db::name('shop_order')->where($where)->count();  //待付款
		$where['status'] = '2';
		$data[2] = Db::name('shop_order')->where($where)->count();  //待发货 
		$where['status'] = '3';
		$data[3] = Db::name('shop_order')->where($where)->count();  //待收货 
		$where['status'] = '4';
		$data[4] = Db::name('shop_order')->where($where)->count();  //完成
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
		// 	array('goodsid'=>35,'goodsnum'=>'3'),
		// 	array('goodsid'=>37,'goodsnum'=>'1'),	
		// 	array('goodsid'=>30,'goodsnum'=>'1'),	
		// ); 

		$input = input('post.');
		$validate = new Validate($rule,$msg);
		if(!$validate->check($input)){
		    return json(['msg'=>$validate->getError(),'code'=>0]);
		}
		$insertData = [];  //插入订单数据 参数数组
		// 购物车下单 读取购物车商品
		if (!empty($input['cartid'])) {
			$input['goodsinfo'] = [];
			$d = Db::name('shop_cart')->where('id','in',$input['cartid'])->field('*')->select();
			foreach ($d as $key => $value) {
				$input['goodsinfo'][$key]['goodsid'] = $value['goodsid'];
				$input['goodsinfo'][$key]['goodsnum'] = $value['goodsnum'];
			}
			$insertData['cartid'] = $input['cartid'];
		}



		$goodsInfo = $input['goodsinfo'];

		$cid = ''; //不同区的商品不能合并结算 套餐和商品不能合并结算
		foreach ($goodsInfo as $key => $value) {
			//查询商品库存 和 是否下架  
			$goodsInfo = Db::name('shop_goods')->where('id',$value['goodsid'])->field('name,num,is_under,cid')->find();
			if ($goodsInfo['is_under'] == '1' || empty($goodsInfo)) {
				return json(['code'=>0,'data'=>'','msg'=>$goodsInfo['name'].'商品已经下架,请重新下单！']);
				break;
			}

			// if ($goodsInfo['num'] < $value['goodsnum']) {
			// 	return json(['code'=>0,'data'=>'','msg'=>$goodsInfo['name'].'商品库存不足，请重新下单！']);
			// 	break;
			// }
			if (!empty($cid) && $cid !== $goodsInfo['cid']) {
				return json(['code'=>0,'data'=>'','msg'=>'请将窥探和商城分开下单！']);
			}
			$cid = $goodsInfo['cid'];
			
		}

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
		$orderData = Db::name('shop_order')->where('order_sn',input('post.order_sn'))->find();
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
			    Db::name('shop_order')->where('order_sn',input('post.order_sn'))->setField(['status'=>2,'amount'=>$sum,'payment'=>3]); 

        		$goodsinfo = Db::name('shop_order_detail')->where('order_sn',input('post.order_sn'))->select();
        		$newData = [];
        		$where = [];
        		$where['order_sn'] = input('post.order_sn');
        		foreach ($goodsinfo as $key => $value) {
        		    $newData = Db::name('shop_goods')->where('id',$value['goodsid'])->field('name as goodsname,price,imgurl')->find();
        		    $where['goodsid'] = $value['goodsid'];
        		    Db::name('shop_order_detail')->where($where)->update($newData);
        		    // 销量++
        		    Db::name('shop_goods')->where('id',$value['goodsid'])->setInc('hot',$value['goodsnum']);
        		    Db::name('shop_goods')->where('id',$value['goodsid'])->setInc('realhot',$value['goodsnum']);
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
		$order_sn = input('param.order_sn');
		if (empty($order_sn)) {
			return json(['code'=>0,'data'=>'','msg'=>'参数异常']);
		}

		$re = Db::name('shop_order')->where('order_sn',$order_sn)->update(['status'=>'0']);
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

		$re = Db::name('shop_order')->where('order_sn',$order_sn)->update(['is_delete'=>1]);
		if ($re > 0) {
			return json(['code'=>1,'data'=>'','msg'=>'删除成功']);
		}
		return json(['code'=>0,'data'=>'','msg'=>'删除失败']);
	}


	/** 检测抢购订单 是否成功（倒计时走完） 并更新 相关信息
	 *
	 * return 1 成功 0 失败
	 */
	public function checkSpySuccess()
	{
		$list = Db::name('shop_spying_goods')->field('*')->where('status','1')->select();
		if (empty($list)) {
			return 1;
		}
		Db::startTrans();
		try{
			foreach ($list as $k => $v) {
				$goodsInfo = Db::name('shop_goods')->field('countdown,id,name,imgurl,canshu,once_price,price,times')->where('id',$v['goodsid'])->find();
				// 抢购成功  更新status为3  success 插入一条记录 order插入一条待发货记录，goods 更新times+1 last_wintime，等信息
				if ((time()-strtotime($v['created_at']))/3600 > $goodsInfo['countdown']) {
					$userInfo = Db::name('users')->field('id,phone,nickname')->find($v['userid']);
					// 添加中奖记录
				    Db::name('shop_spying_goods')->where('id',$v['id'])->setField('status','3');
				    $successData['goodsid'] = $v['goodsid'];
				    $successData['goodsname'] = $goodsInfo['name'];
				    $successData['goodsprice'] = $goodsInfo['price'];
				    $successData['goodsimgurl'] = $goodsInfo['imgurl'];
				    $successData['goodscanshu'] = $goodsInfo['canshu'];
				    $successData['last_amount'] = $v['sur_price'];
				    $successData['sur_price'] = $v['sur_price'];
				    $successData['once_price'] = $goodsInfo['once_price'];
				    $successData['is_spy'] = '1';    // 0 : 抢购成功 1：窥探成功
				    $successData['usermobile'] = $userInfo['phone'];
				    $successData['userid'] = $v['userid'];
				    $successData['username'] = $userInfo['nickname'];
				    $successData['payment'] = $v['payment'];
				    $successData['times'] = $v['times'];
				    $successData['spyingid'] = $v['id'];
				    $successData['created_at'] = date("Y-m-d H:i:s");
				    Db::name('shop_spy_success')->insert($successData);

				    //添加 中奖待发货订单
				    $orderData['order_sn'] =  $v['spy_sn'];
				    $orderData['uid'] =  $v['userid'];
				    $orderData['buyer_name'] =  $v['buyer_name'];
				    $orderData['buyer_phone'] =  $v['buyer_phone'];
				    $orderData['amount'] =  $v['sur_price'];
				    $orderData['money'] =  $v['sur_price'];
				    $orderData['province'] =  $v['province'];
				    $orderData['city'] =  $v['city'];
				    $orderData['area'] =  $v['area'];
				    $orderData['detail'] =  $v['detail'];
				    $orderData['status'] =  '2';    //抢购倒计时结束  直接生成待发货订单
				    $orderData['payment'] =  $v['payment'];
				    $orderData['created_at'] =  date("Y-m-d H:i:s");
				    $orderData['remark'] =  '窥探商品抢购成功';
				    $orderid = Db::name('shop_order')->insertGetid($orderData);

				    $orderDetail['orderid'] = $orderid;
				    $orderDetail['order_sn'] = $v['spy_sn'];
				    $orderDetail['goodsid'] = $v['goodsid'];
				    $orderDetail['goodsname'] = $goodsInfo['name'];
				    $orderDetail['goodsnum'] = '1';   // 窥探商品数量 1
				    $orderDetail['price'] = $goodsInfo['price'];
				    $orderDetail['cid'] = '2';    // 窥探商品分类
				    $orderDetail['imgurl'] = $goodsInfo['imgurl'];
				    $orderDetail['created_at'] = date("Y-m-d H:i:s");
				    Db::name('shop_order_detail')->insert($orderDetail);

				    //重置商品更新times+1 last_wintime，等信息
				    $updateData['sur_price'] = $goodsInfo['price'];
				    $updateData['spy_price'] = '0';
				    $updateData['status'] = '1'; //0:正常 1：中奖间隔期
				    $updateData['last_wintime'] = date('Y-m-d H:i:s'); 
				    $updateData['times'] = $goodsInfo['times']+1; 
				    Db::name('shop_goods')->where('id',$v['goodsid'])->update($updateData);
				}
			}
		    // 提交事务
		    Db::commit();    
		    return 1;
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		    // return $e->getMessage();
		    return 0;
		}

	}


	/**
	 * 随机生成订单号
	 */
 	public function orderNum(){
	    do{
	        $num = date('YmdHis').rand(100,999);
	    }while(Db::name('shop_order')->where(['order_sn'=>$num])->find());
	    return $num;
	}




	
	
}