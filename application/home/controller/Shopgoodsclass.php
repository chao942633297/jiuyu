<?php 
namespace app\home\controller;

use think\Controller;
use app\backsystem\model\ShopGoodsClassModel;
/**
* 商品分类-酒
*/
class Shopgoodsclass extends Controller
{
	#展示商品分类列表 
	public function shopGoodsclassList()
	{
		$shopgoodsclass = new ShopGoodsClassModel();
		//商品分类
		$shopGoodsclassList  = $shopgoodsclass->getShopGoodsClassList('1=1','id,classname');
		
		return json(['code'=>1,'data'=>$shopGoodsclassList,'msg'=>'success']);

	}


	//获取商品分类详情
	public function shopGoodsclassInfo()
	{
		$id = !empty(input('id')) && input('id') > 0 ? input('id') : exit(json_encode(['code'=>0,'data'=>'','msg'=>'参数异常']));
		$shopgoodsclass = new ShopGoodsClassModel();
		$goodsInfo = $shopgoodsclass->getOneShopGoodsclass($id,"*");
		return json(['code'=>1,'data'=>$goodsInfo,'msg'=>'success']);
	}
}