<?php 

namespace app\home\controller;
use app\backsystem\model\UserModel;
use think\Controller;
use think\Request;
use think\Validate;
use think\Db;


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
		$cartData = Db::name('shop_cart')->where('userid',$this->userId)->select();
		$gids = array();
		foreach ($cartData as $key => $value) {
			$goodsinfo = Db::name('shop_goods')->where('id',$value['goodsid'])->field('is_under')->find();
			if (!empty($goodsinfo) && $goodsinfo['is_under'] == '0') {
				$gids[] = $value['goodsid'];
			}else if (empty($goodsinfo) || $goodsinfo['is_under'] == '1') {
				//删除购物车中添加过之后被下架或者删除的商品记录 以防垃圾数据
				Db::name('shop_cart')->where(['userid'=>$this->userId,'goodsid'=>$value['goodsid']])->delete();
			}
		}

		$cartList = Db::name('shop_goods')->alias('g')->join('shop_cart c','c.goodsid = g.id','LEFT')->where('c.userid',$this->userId)->where('c.goodsid','in',$gids)->page($page,$limit)->field('*')->select();

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

		// $_POST['goodsid'] = '35';
		// $_POST['goodsnum'] = '2';

		$input = input('post.');
		$validate = new Validate($rule,$msg);
		if(!$validate->check($input)){
		    return json(['msg'=>$validate->getError(),'code'=>0]);
		}

		$insertData['goodsid'] = input('post.goodsid');
		$insertData['userid'] = $this->userId;

		//查询商品库存 和是否下架
		$goodsInfo = Db::name('shop_goods')->where('id',$insertData['goodsid'])->field('num,is_under,cid')->find();
		if ($goodsInfo['is_under'] == '1' || empty($goodsInfo)) {
			return json(['code'=>0,'data'=>'','msg'=>'商品已经下架']);
		}
		if ($goodsInfo['cid'] == 2) {
			return json(['code'=>0,'data'=>'','msg'=>'窥探商品不能添加到购物车']);	
		}
		// if ($goodsInfo['num'] < input('post.goodsnum')) {
		// 	return json(['code'=>0,'data'=>'','msg'=>'商品库存不足']);
		// }
	
		// 首先判断购物车中是否已经存在相同规格的商品
		$num = Db::name('shop_cart')->where($insertData)->count();
		if ($num > 0) {
			$goodsnum = input('post.goodsnum');	
			//修改购物车中对应商品的数量
			$r = Db::name('shop_cart')->where($insertData)->setInc('goodsnum', $goodsnum);
			if ($r) {
				return json(['code'=>1,'data'=>'','msg'=>'添加购物车成功！']);
			}else{
				return json(['code'=>0,'data'=>'','msg'=>'添加购物车失败！']);
			}
		}else{
			$insertData['goodsnum'] = input('post.goodsnum');
			$insertData['created_at'] = date("Y-m-d H:i:s");

			$re = Db::name('shop_cart')->insertGetid($insertData);
			if ($re) {
				return json(['code'=>1,'data'=>'','msg'=>'添加购物车成功！']);
			}
			return json(['code'=>0,'data'=>'','msg'=>'添加购物车失败！']);
			
		}
	}



	/**
	 * 请求订单数据
	 * 请求方式 post
	 * @param [[goodsid,goodsnum],[goodsid,goodsnum],[goodsid,goodsnum]] 商品id
	 * return 商品图片 数量 单价 总价 默认地址  余额
	 */
	public function createOrder()
	{
		// $_POST['data'] = [['goodsid'=>'35','goodsnum'=>'1'],['goodsid'=>'37','goodsnum'=>'2']];
		// $_POST['data'] = [['goodsid'=>'45','goodsnum'=>1]];

		$postData = input('post.');
		$input = $postData['data'];
		if (!is_array($input) || empty($input)) {
			return json(['code'=>0,'data'=>'','msg'=>'提交数据类型错误，请重新下单']);
		}

		$userid = $this->userId;
		$reData = [];
		$user = UserModel::get($this->userId);
		$reData['balance'] = $user->balance;
		if (Db::name('address')->where(['uid'=>$userid,'is_default'=>'1'])->count()) {
			$address = Db::name('address')->field('*')->where(['uid'=>$userid,'is_default'=>'1'])->order('id desc')->limit('1')->find();

		}else if (Db::name('address')->where(['uid'=>$userid])->count()) {
			$address = Db::name('address')->field('*')->where(['uid'=>$userid])->order('id desc')->limit('1')->find();
		}else{
			$address = '';
			
		}
		$reData['address'] = $address;
		
		//查询商品是否下架
		$goodsInfo = [];
		$reData['amount'] = 0;
		foreach ($input as $key => $value) {
			$goodsInfo[$key] = Db::name('shop_goods')->where('id',$value['goodsid'])->field('is_under,cid,name as goodsname,imgurl as goodsimgurl,price,times')->find();
			if ($goodsInfo[$key]['is_under'] == '1' || empty($goodsInfo[$key])) {
				return json(['code'=>0,'data'=>'','msg'=>'部分商品已经下架,请重新下单']);
			}
			if ($goodsInfo[$key]['cid'] == 2) {
				// 窥探商品需要从spy_record里面查询
				$spyPrice = Db::name('shop_spy_record')->field('sur_price')->where(['goodsid'=>$value['goodsid'],'userid'=>$userid,'times'=>$goodsInfo[$key]['times']])->order('id desc')->limit('1')->find(); 
				if (empty($spyPrice)) {
					$goodsInfo[$key]['price'] = $goodsInfo[$key]['price'];
				}else{
					$goodsInfo[$key]['price'] = $spyPrice['sur_price'];
				}
				$goodsInfo[$key]['goodsnum'] = '1';
			}	
			$goodsInfo[$key]['goodsnum'] = $value['goodsnum'];
			$reData['amount'] += $goodsInfo[$key]['price']*$goodsInfo[$key]['goodsnum'];
		}	
		$reData['amount'] = sprintf("%01.2f",$reData['amount']);
		$reData['goodsInfo'] = $goodsInfo;

		return json(['code'=>1,'data'=>$reData,'msg'=>'success']);
	}


	/**
	 * 批量更新修改购物车商品
	 * 请求方式 
	 * @param data  array   => id:购物车id，goodsid:  goodsnum: 
	 */
	public function cartGoodsUpdate()
	{
		// 整理更新数据
		$input = input('post.');
		$data = $input['data'];
	
		
		if (empty($data)) {
			return json(['code'=>0,'data'=>'','msg'=>'提交数据错误，删除失败']);
		}

		foreach ($data as $key => $value) {
			Db::name('shop_cart')->where(['id'=>$value['id']])->update(['goodsnum' => $value['goodsnum']]);
		}

		return json(['code'=>1,'data'=>'','msg'=>'修改购物车成功']);
	
	}

	//删除购物车商品  批量删除 
	/*
	 *
	 * @param 商品ID array
	 *
	 */
	public function cartGoodsDel()
	{
		$input = input('post.');
		$data = $input['data'];

		if (empty($data)) {
			return json(['code'=>0,'data'=>'','msg'=>'提交数据错误，删除失败']);
		}
		$delData = [];
		foreach ($data as $key => $value) {
			$delData[] = $value;
		}

		$re = Db::name('shop_cart')->delete($delData);
		if ($re > 0) {
			return json(['code'=>1,'data'=>'','msg'=>'删除成功']);
		}
		return json(['code'=>0,'data'=>'','msg'=>'删除失败']);
	}



	//一键清空购物车 
	public function cartClear()
	{
		$re = Db::name('shop_cart')->where('userid',$this->userId)->delete();
		if ($re > 0) {
			return json(['code'=>1,'data'=>'','msg'=>'清空购物车成功']);
		}else{
			return json(['code'=>0,'data'=>'','msg'=>'清空购物车失败或者购物车已空']);
		}
	}
	
}