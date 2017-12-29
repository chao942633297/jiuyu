<?php 

namespace app\home\controller;
use think\Controller;
use think\Request;
use think\Validate;


class Shopcart extends Base
{
	protected $userId;

	public function _initialize()
	{
	    parent::_initialize(); // 判断用户是否登陆
	    $this->userId = session('home_user_id');
	    // $this->userId = input('param.userId');
	    if ($this->userId < 0 || empty($this->userId)) {
	    	return json(['code'=>0,'data'=>'','msg'=>'获取用户信息失败']);
	    }
	}



	//购物车列表
	/*
	 *
	 * @param limit 每页显示数量 默认10条
	 * @param page 页码 
	 * 
	 */
	public function cartList()
	{
		//加分页
		$page = !empty(input('param.page')) && input('param.page') > 0 ? input('param.page') : '1' ;
		$limit = !empty(input('param.limit')) && input('param.limit') > 0 ? input('param.limit') : '10' ;

		//过滤商品已经被后台下架或者被删除的商品数据
		$cartData = db('shop_cart')->where('userid',$this->userId)->select();
		$gids = array();
		foreach ($cartData as $key => $value) {
			$goodsinfo = db('shop_goods')->where('id',$value['goodsid'])->field('is_under')->find();
			if (!empty($goodsinfo) && $goodsinfo['is_under'] == '0') {
				$gids[] = $value['goodsid'];
			}
		}

		$cartList = db('shop_cart')->alias('c')->join('shop_goods g','c.goodsid = g.id','RIGHT')->where('c.userid',$this->userId)->where('c.goodsid','in',$gids)->page($page,$limit)->field('*')->select();

		return json(['code'=>1,'data'=>$cartList,'msg'=>'success']);
	}


	/**
	 * 添加商品到购物车
	 * 请求方式 
	 * @param goodsid 商品id
	 * @param goodsnum  数量
	 */
	public function cartGoodsAdd()
	{
		$rule = [
		    'goodsid'=>'require',
		    'goodsnum'=>'require',
		];
		$msg = [
		    'goodsid.require'=>'商品ID不能为空',
		    'goodsnum.require'=>'商品数量不能为空',
		];

		$input = Request::instance()->get();
		$validate = new Validate($rule,$msg);
		if(!$validate->check($input)){
		    return json(['msg'=>$validate->getError(),'code'=>0]);
		}

		$insertData['goodsid'] = input('param.goodsid');
		$insertData['userid'] = $this->userId;

		//查询商品库存 和是否下架
		$kucun = db('shop_goods')->where('id',$insertData['goodsid'])->field('num,is_under')->find();
		if ($kucun['is_under'] == '1') {
			return json(['code'=>0,'data'=>'','msg'=>'商品已经下架']);
		}
		if ($kucun['num'] < input('param.goodsnum')) {
			return json(['code'=>0,'data'=>'','msg'=>'商品库存不足']);
		}
	
		// 首先判断购物车中是否已经存在相同规格的商品
		$num = db('shop_cart')->where($insertData)->count();
		if ($num > 0) {
			$goodsnum = input('param.goodsnum');	
			//修改购物车中对应商品的数量
			$r = db('shop_cart')->where($insertData)->setInc('goodsnum', $goodsnum);
			if ($r) {
				return json(['code'=>1,'data'=>'','msg'=>'添加购物车成功！']);
			}else{
				return json(['code'=>0,'data'=>'','msg'=>'添加购物车失败！']);
			}
		}else{
			$insertData['goodsnum'] = input('param.goodsnum');
			$insertData['created_at'] = date("Y-m-d H:i:s");

			$re = db('shop_cart')->insertGetid($insertData);
			if ($re) {
				return json(['code'=>1,'data'=>'','msg'=>'添加购物车成功！']);
			}
			return json(['code'=>0,'data'=>'','msg'=>'添加购物车失败！']);
			
		}
	}


	/**
	 * 批量更新修改购物车商品
	 * 请求方式 
	 * @param data  array   => id:购物车id，goodsid:  goodsnum: 
	 */
	public function cartGoodsUpdate()
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
			$updateData[$key]['cartid'] = $value['id'];  
			
		}

		$re = db('shop_cart')->saveAll($updateData);
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
	
		// // 首先判断购物车中是否已经存在相同规格的商品
		// $num = db('shop_cart')->where($insertData)->count();
		// if ($num > 0) {
		// 	$goodsnum = input('param.goodsnum');	
		// 	//修改购物车中对应商品的数量
		// 	$r = db('shop_cart')->where($insertData)->setInc('goodsnum', $goodsnum);
		// 	if ($r) {
		// 		return json(['code'=>1,'data'=>'','msg'=>'添加购物车成功！']);
		// 	}else{
		// 		return json(['code'=>0,'data'=>'','msg'=>'添加购物车失败！']);
		// 	}
		// }else{
		// 	$insertData['goodsnum'] = input('param.goodsnum');
		// 	$insertData['created_at'] = date("Y-m-d H:i:s");

		// 	$re = db('shop_cart')->insertGetid($insertData);
		// 	if ($re) {
		// 		return json(['code'=>1,'data'=>'','msg'=>'添加购物车成功！']);
		// 	}
		// 	return json(['code'=>0,'data'=>'','msg'=>'添加购物车失败！']);
			
		// }
	}

	//删除购物车商品  批量删除 
	/*
	 *
	 * @param 商品ID array
	 *
	 */
	public function cartGoodsDel()
	{
		$data = input('param.data');

		$re = db('shop_cart')->delete($data);
		if ($re > 0) {
			return json(['code'=>1,'data'=>'','msg'=>'删除成功']);
		}
		return json(['code'=>0,'data'=>'','msg'=>'删除失败']);
	}



	//一键清空购物车 
	public function cartClear()
	{
		$re = db('shop_cart')->where('userid',$this->userId)->delete();
		if ($re > 0) {
			return json(['code'=>1,'data'=>'','msg'=>'清空购物车成功']);
		}else{
			return json(['code'=>0,'data'=>'','msg'=>'清空购物车失败或者购物车已空']);
		}
	}
	
}