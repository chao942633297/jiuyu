<?php 

namespace app\home\controller;
use think\Controller;
use think\Request;
use think\Validate;
use app\home\model\ShopOrderModel;



class Shoporder extends Base
{
	protected $userId;

	public function _initialize()
	{
	    parent::_initialize(); // 判断用户是否登陆
	    session('home_user_id','13');
	    $this->userId = session('home_user_id');
	    // $this->userId = input('param.userId');
	    if ($this->userId < 0 || empty($this->userId)) {
	    	return json(['code'=>0,'data'=>'','msg'=>'获取用户信息失败']);
	    }
	}



	//订单列表
	/*
	 *
	 * @param limit 每页显示数量 默认10条
	 * @param page 页码 
	 * 
	 */
	public function orderList()
	{
		//加分页
		$page = !empty(input('param.page')) && input('param.page') > 0 ? input('param.page') : '1' ;
		$limit = !empty(input('param.limit')) && input('param.limit') > 0 ? input('param.limit') : '10' ;

		//过滤商品已经被后台下架或者被删除的商品数据
		$orderData = db('shop_order')->where('userid',$this->userId)->select();
		$gids = array();
		foreach ($orderData as $key => $value) {
			$goodsinfo = db('shop_goods')->where('id',$value['goodsid'])->field('is_under')->find();
			if (!empty($goodsinfo) && $goodsinfo['is_under'] == '0') {
				$gids[] = $value['goodsid'];
			}
		}

		$orderList = db('shop_order')->alias('c')->join('shop_goods g','c.goodsid = g.id','RIGHT')->where('c.userid',$this->userId)->where('c.goodsid','in',$gids)->page($page,$limit)->field('*')->select();

		return json(['code'=>1,'data'=>$orderList,'msg'=>'success']);
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
		    'buyer_name'=>'require|max:25',
		    'buyer_phone'=>'require|/^1[3456789]\d{9}$/',
		];
		$msg = [
		    'goodsinfo.require'=>'商品信息不能为空',
		    'province'=>'省不能为空',
		    'city'=>'市不能为空',
		    'area'=>'地区不能为空',
		    'buyer_name'=>'用户名不能为空|名称最多不能超过25个字符',
		    'buyer_phone'=>'手机号不能为空|请输入正确的手机号',
		];

		$_POST['province'] = '河南省'; 
		$_POST['city'] = '郑州市'; 
		$_POST['area'] = '金水区北三环中州大道963号康杰大酒店'; 
		$_POST['buyer_name'] = '王先生'; 
		$_POST['buyer_phone'] = '18236952689'; 
		$_POST['goodsinfo'] = array(
			// array('goodsid'=>34,'goodsnum'=>'3'),
			array('goodsid'=>35,'goodsnum'=>'1'),	
			// array('goodsid'=>33,'goodsnum'=>'1'),
			array('goodsid'=>32,'goodsnum'=>'1'),
			array('goodsid'=>31,'goodsnum'=>'1'),
		); 

		$input = input('post.');
		$validate = new Validate($rule,$msg);
		if(!$validate->check($input)){
		    return json(['msg'=>$validate->getError(),'code'=>0]);
		}
		$goodsInfo = $input['goodsinfo'];

		foreach ($goodsInfo as $key => $value) {
			//查询商品库存 和 是否下架 
			$kucun = db('shop_goods')->where('id',$value['goodsid'])->field('name,num,is_under')->find();
			if ($kucun['is_under'] == '1' || empty($kucun)) {
				return json(['code'=>0,'data'=>'','msg'=>$kucun['name'].'商品已经下架,请重新下单！']);
				break;
			}
			if ($kucun['num'] < $value['goodsnum']) {
				return json(['code'=>0,'data'=>'','msg'=>$kucun['name'].'商品库存不足，请重新下单！']);
				break;
			}
		}

		$insertData['goodsinfo'] = $input['goodsinfo'];
		$insertData['province'] = $input['province'];
		$insertData['city'] = $input['city'];
		$insertData['area'] = $input['area'];
		$insertData['buyer_name'] = $input['buyer_name'];
		$insertData['buyer_phone'] = $input['buyer_phone'];
		$insertData['uid'] = $this->userId;
		$insertData['order_sn'] = $this->orderNum();


        $ShopOrder = new ShopOrderModel();
        $flag = $ShopOrder->addShopOrder($insertData);
        return json([$flag['code'], $flag['data'], $flag['msg']]);

	}


	/**
	 * 批量更新修改订单商品
	 * 请求方式 
	 * @param data  array   => id:订单id，goodsid:  goodsnum: 
	 */
	public function orderGoodsUpdate()
	{
		//数据格式验证
		// $rule = [
		//     'goodsid'=>'require',
		//     'goodsnum'=>'require',
		// ];
		// $msg = [
		//     'goodsid.require'=>'商品ID不能为空',
		//     'goodsnum.require'=>'商品数量不能为空',
		// ];

		// $input = Request::instance()->get();
		// $validate = new Validate($rule,$msg);
		// if(!$validate->check($input)){
		//     return json(['msg'=>$validate->getError(),'code'=>0]);
		// }

		// 整理更新数据
		$data = input('param.data');
		$updateData = array();
		foreach ($data as $key => $value) {
			// $updateData[$key]['userid'] = $this->userId;  
			// $updateData[$key]['goodsid'] = $value['goodsid'];  
			// 查询商品是否已经下架 和库存
			$where = array();
			$where['goodsid'] = $value['goodsid'];
			$kucun = db('shop_goods')->where($where)->field('num,is_under')->find();
			if ($kucun['is_under'] == '1') {
				return json(['code'=>0,'data'=>'','msg'=>'部分商品已经下架']);
			}
			if ($kucun['num'] < $value['goodsnum']) {
				return json(['code'=>0,'data'=>'','msg'=>'部分商品库存不足']);	
			}
			
			$updateData[$key]['goodsnum'] = $value['goodsnum'];  
			$updateData[$key]['orderid'] = $value['id'];  
			
		}

		$re = db('shop_order')->saveAll($updateData);
		if ($re > 0) {
			return json(['code'=>1,'data'=>'','msg'=>'修改成功']);
		}

		return json(['code'=>0,'data'=>'','msg'=>'商品没有变化']);
		// $insertData['userid'] = $this->userId;

		// //查询商品库存
		// $kucun = db('shop_goods')->where('id',$insertData['goodsid'])->field('num')->find();
		// if ($kucun['num'] < input('param.goodsnum')) {
		// 	return json(['code'=>0,'data'=>'','msg'=>'商品库存不足']);
		// }
	
		// // 首先判断订单中是否已经存在相同规格的商品
		// $num = db('shop_order')->where($insertData)->count();
		// if ($num > 0) {
		// 	$goodsnum = input('param.goodsnum');	
		// 	//修改订单中对应商品的数量
		// 	$r = db('shop_order')->where($insertData)->setInc('goodsnum', $goodsnum);
		// 	if ($r) {
		// 		return json(['code'=>1,'data'=>'','msg'=>'添加订单成功！']);
		// 	}else{
		// 		return json(['code'=>0,'data'=>'','msg'=>'添加订单失败！']);
		// 	}
		// }else{
		// 	$insertData['goodsnum'] = input('param.goodsnum');
		// 	$insertData['created_at'] = date("Y-m-d H:i:s");

		// 	$re = db('shop_order')->insertGetid($insertData);
		// 	if ($re) {
		// 		return json(['code'=>1,'data'=>'','msg'=>'添加订单成功！']);
		// 	}
		// 	return json(['code'=>0,'data'=>'','msg'=>'添加订单失败！']);
			
		// }
	}

	//删除订单商品  批量删除 
	/*
	 *
	 * @param 商品ID array
	 *
	 */
	public function orderGoodsDel()
	{
		$data = input('param.data');

		$re = db('shop_order')->delete($data);
		if ($re > 0) {
			return json(['code'=>1,'data'=>'','msg'=>'删除成功']);
		}
		return json(['code'=>0,'data'=>'','msg'=>'删除失败']);
	}



	//一键清空订单 
	public function orderClear()
	{
		$re = db('shop_order')->where('userid',$this->userId)->delete();
		if ($re > 0) {
			return json(['code'=>1,'data'=>'','msg'=>'清空订单成功']);
		}else{
			return json(['code'=>0,'data'=>'','msg'=>'清空订单失败或者订单已空']);
		}
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