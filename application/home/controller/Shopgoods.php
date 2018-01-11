<?php 
namespace app\home\controller;

use think\Controller;
use app\backsystem\model\ShopGoodsModel;
/**
* 商品-酒
*/
class Shopgoods extends Controller
{
	#展示商品列表 
	public function shopGoodsList()
	{
		$shopgoods = new ShopGoodsModel();
		//根据sort 获取销售中（is_under=0）的商品
		$page = !empty(input('param.page')) ? input('param.page') : '1';
		$limit = !empty(input('param.limit')) ? input('param.limit') : '10';
		$cid = !empty(input('param.cid')) ? input('param.cid') : '1';  //分类ID
		$offset = ($page-1)*$limit;
		$where = array();
		$where['is_under'] = '0';
		if (!empty($cid)) {
			$where['cid'] = $cid;
		}
		$shopGoodsList  = $shopgoods->getShopGoodsByWhere($where, $offset, $limit,'sort desc,id desc','*');
		
		return json(['code'=>1,'data'=>$shopGoodsList,'msg'=>'success']);

	}


	//获取商品详情
	public function shopGoodsInfo()
	{
		$id = !empty(input('id')) && input('id') > 0 ? input('id') : exit(json_encode(['code'=>0,'data'=>'','msg'=>'参数异常']));
		$shopgoods = new ShopGoodsModel();
		$goodsInfo = $shopgoods->getOneShopGoods($id,"*");
		return json(['code'=>1,'data'=>$goodsInfo,'msg'=>'success']);
	}
}