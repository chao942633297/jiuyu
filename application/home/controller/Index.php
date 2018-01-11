<?php

namespace app\home\controller;

use app\backsystem\model\ClassModel;
use app\backsystem\model\GoodsModel;
use think\Controller;
use think\Db;
use think\Request;
use Service\Check;

class Index extends Controller
{
    /**
     * 首页
     */
    public function index()
    {
        #只查询三条
        $goods   = Db::table('sql_goods')
            ->where(['is_jing'=>1,'id'=>['in',[1,2,3],'is_delete'=>1]])
            ->limit(3)->select();
        $goodInfo = [];
        foreach ($goods as $key => $value) {
            $goodInfo[$key]['id']   = $value['id'];
            $goodInfo[$key]['name'] = $value['name'];
            $goodInfo[$key]['img']  = $value['img'];
        }
        //轮播图
        $lunbo = db('lunbo')->where('sort',1)->select();
        //购车播报
        $broadcast = db('order')->where('status',2)->order('id','desc')->select();
        //商城区
        $shopGoods = Db::table('sql_shop_goods')
            ->where('is_under',0)
            ->order('sort','asc')->select();
        #车辆品牌
//        $class = db('class')->limit(10)->select();
        return json(['code'=>200,'goods'=>$goodInfo,'lunbo'=>$lunbo,'broadcast'=>$broadcast,'shopGoods'=>$shopGoods,'msg'=>'查询成功']);

    }


    /**
     * @return \think\response\Json
     * 搜索页--推荐发现
     */
    public function recommend(){
        $class = ClassModel::all(function($query){
            $query->order('sort','asc');
            $query->limit(7);
        });
        $return = [];
        foreach($class as $key=>$val){
            $return[$key]['name'] = $val['class'];
        }
        return json(['data'=>$return,'msg'=>'查询成功','code'=>200]);
    }





    /**
     * 搜索
     */
    public function  search()
    {

        $where   = [];
        #品牌
        if (input('class_id') != '') {
           $where['cid']  = input('class_id');
        }
        #车系
        if (input('type_id') != '') {
           $where['id']  = input('type_id');
        }


        #车辆名或品牌
        if (input('class_name') != '' && input('class_id') != '') {
            $where['name'] = ['like','%'.input('class_name').'%'];
        }
        #品牌id不存在
        if (input('class_name') != '' && input('class_id') == '') {
            $where['cid']  = db('class')->where('class','like','%'.input('class_name').'%')->value('id');
            if (!$where['cid']) {
                unset($where['cid']);
                $where['name'] = ['like','%'.input('class_name').'%'];
            }
        }
        $sort = 'asc';
        if(input('sort') != ''){
            $sort = input('sort');
        }
         $info = db('goods')->where($where)->order('id',$sort)->select();
         $Carinfo = [];
         foreach ($info as $key => $value) {
             $Carinfo[$key]['name'] = $value['name']; 
             $Carinfo[$key]['img'] = $value['img']; 
             $Carinfo[$key]['price'] = $value['price']; 
             $Carinfo[$key]['id'] = $value['id']; 
         }
         return json(['code'=>200,'info'=>$Carinfo,'msg'=>'success']);
    }


    /**
     * 车型
     */
    public function  carType()
    {
        $class = '';
        if (input('type') == 1) {
            $class = db('class')->select();
            #按字母分组
            $Check = new Check();
            $class = $Check->makeGroup($class);
            #字母排序
        }else{
            if (input('class_id') == '') {
              return json(['code'=>101,'msg'=>'请先选择品牌']);
            }
            $info = db('goods')->where(['cid'=>input('class_id')])->field("type_id,group_concat(id) as groups")->group('type_id')->select();
            if (!$info) {
                return json(['code'=>102,'msg'=>'该品牌下没有产品']);
            }
            $class_name = db('goods_type')->column('name','id');
            foreach ($info as $key => $value) {
                $class[$class_name[$value['type_id']]] = objToarray(Db::table('sql_goods')
                    ->whereIn('id',$value['groups'])->field('id,name')->select()) ;
            }
        }
        return json(['code'=>200,'info'=>$class,'msg'=>'success']);

    }

    /**
     * 购买时选择车型
     */
    public function  buyType()
    {
        $class = db('class')->select();
        #按字母分组
        $Check = new Check();
        $class = $Check->makeGroup($class);
        return json(['code'=>200,'info'=>$class,'msg'=>'success']);
    }

    /**
     * 购买时选择车型详情
     */
    public function  buyTypeDetail()
    {
        $classId = input('class_id');
        if(empty($classId)){
            return json(['msg'=>'缺少参数','code'=>1001]);
        }
        $goods = new GoodsModel();
        $goods = $goods->where(['cid'=>$classId])->select();
        $info = [];
        foreach ($goods as $key => $value) {
                $info[$key]['id'] =$value['id'];
                $info[$key]['name'] =$value['name'];
                $info[$key]['remark'] =$value['remark'];
                $info[$key]['type_name'] =$value->getType['name'];
        }
        return json(['code'=>200,'info'=>$info,'msg'=>'success']);
    }

    //车辆详情
    public function carDetail(Request $request){
        $carId = $request->param('carId');
        $good = new GoodsModel();
        $detail = $good->where(['id'=>$carId,'is_delete'=>1])->find();
        if(!$detail){
            return json(['msg'=>'车辆不存在或下架','code'=>1001]);
        }
//        dump($detail->lunbo);die;
        $return = [];
        $return['limg'] = $detail['lunbo'];
        $return['name'] = $detail['name'];
        $return['price'] = $detail['price'];
        $return['canshu'] = $detail['canshu'];
        $return['description'] = $detail['description'];
        return json(['data'=>$return,'msg'=>'查询成功','code'=>200]);
    }





















}
