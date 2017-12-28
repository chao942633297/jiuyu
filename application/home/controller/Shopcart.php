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
	    if ($this->userId < 0 || empty($this->userId)) {
	    	return json(['code'=>0,'data'=>'','msg'=>'获取用户信息失败']);
	    }
	}



	//购物车列表
	public function cartList()
	{
		//加分页
		$cartList = db('shop_cart')->where('userid',$this->userId)->select();
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


	//删除购物车商品  批量删除 商品ID array
	public function cartGoodsDel()
	{

		return json(['code'=>1,'data'=>'','msg'=>'success']);
	}



	//清空购物车 
	public function cartClear()
	{

		return json(['code'=>1,'data'=>'','msg'=>'success']);
	}
	
}