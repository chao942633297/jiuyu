<?php 
namespace app\home\controller;

use think\Controller;
use app\backsystem\model\GoodsModel;
/**
* 商品-车辆
*/
class Goods extends Controller
{
	#展示车辆
	public function carInfo()
	{
		$goods = new GoodsModel();
		#只查询三条 主打车辆
		$goods  = $goods->where(['is_jing'=>1])->limit(3)->select();
		foreach ($goods as $k => $v) {
			$goods[$k]['type_name'] = $v->getType['name'];
		}
		#车辆品牌
		$class = db('class')->limit(10)->select();
		dump($class);die;
		return json(['code'=>200,'goods'=>$goods,'class'=>$class,'msg'=>'success']);

	}
}