<?php

namespace app\backsystem\controller;

use app\backsystem\model\GoodsModel;

class Goods extends Base
{
    //商品列表
    public function index(){
        if(request()->isAjax()){
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = $whereb = $wherec =$brank = [];
            $brankid = [];
            $carid = [];
            //商品名称查询
            if (isset($param['username']) && !empty($param['username'])) {
                $where['name'] = ['like', '%' . $param['username'] . '%'];
            }
            if (isset($param['status']) && $param['status'] != 0) {
                $where['is_delete'] = ['like', '%' . $param['status'] . '%'];
            }

            //品牌查询
            if (isset($param['brank']) && !empty($param['brank'])) {
                $whereb['class'] = ['like', '%' . $param['brank'] . '%'];
            }
            if($whereb){
                $brankid = db('class')->where($whereb)->column('id');
            }
            //车系查询
            if (isset($param['car']) && !empty($param['car'])) {
                $wherec['name'] = ['like', '%' . $param['car'] . '%'];
            }
            if($wherec){
                $carid = db('goods_type')->where($wherec)->column('id');
            }
            $good = new GoodsModel();
            $selectResult = $good->all(function($query)use($where,$offset,$limit,$brankid,$carid){
                $query->order('id','desc');
                $query->where($where);
                if($brankid){
                    $query->where('cid','in',$brankid);
                }
                if($carid){
                    $query->where('type_id','in',$carid);
                }
            });

            $status = config('goods_status');
            foreach($selectResult as $key=>$vo){
                $vo['is_jing'] == 1 ? $selectResult[$key]['is_jing'] = '是':$selectResult[$key]['is_jing'] = '否';
                $selectResult[$key]['is_delete'] = $status[$vo['is_delete']];
                $selectResult[$key]['type'] = db('class')->where(['id'=>$vo['cid']])->value('class');
                $selectResult[$key]['type_id'] = db('goods_type')->where(['id'=>$vo['type_id']])->value('name');
                $selectResult[$key]['img'] = "<img src='".$vo['img']."' width='50px' height='50px' />";
                $operate = [
                    '轮播图' => "javascript:goodsthumbnail('".$vo['id']."')",
                    '编辑'   => url('goods/goodsEdit', ['id' => $vo['id']]),
                ];
                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $good->getAllGoods($where);  //总数据
            $return['rows'] = $selectResult;
            $return['data'] = $param;
            return json($return);
        }
        return $this->fetch();
    }

    //添加商品

    public function goodsAdd()
    {
        if(request()->isPost()){
            $param = input('param.');
            $param = parseParams($param['data']);
            $param['create_at'] = time();
            // var_dump($param);die;
            $good = new GoodsModel();
            $flag = $good->insertGoods($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $class = db('class')->select();
        $type = db('goods_type')->select();
        $this->assign([
            'class'=>$class,
            'type'=>$type,
        ]);
        return $this->fetch();

    }

    //遍历商品的轮播图
    public function addGoodsThumbnail(){
         $param = input('param.');
         // var_dump($param['id']);die;
         if (!empty($param['id'])) {
            $thumbnail = db('lunbo')->where(['gid'=>$param['id'],'sort'=>2])->select();
         }else{
            $thumbnail = [];
         }
          // var_dump($thumbnail);return;
        return json(['code' => 1, 'data' => $thumbnail,'id'=>$param['id'], 'msg' => 'success']);
    }

    //添加单个商品的轮播图
    public function savephoto(){
        $param = input('param.');
        $insert['gid'] = $param['gid'];
        $insert['imgurl'] = $param['imgurl'];
        $insert['sort'] = 2;
        $insert['create_at'] = time();
        // array_shift($param);
        // var_dump($param);die;
        $result = db('lunbo')->insert($insert);
        return json(['code' => $result]);
    }

    public function delThumbnail(){
        $id = input('param.id');
        // var_dump($id);die;
        $result = db('lunbo')->where("id=".$id)->delete();
        return json(['code' => $result]);
    }

    //编辑商品
    public function goodsEdit(){
        $good = new GoodsModel();
        if(request()->isPost()){
            $param = input('param.');
            $param = parseParams($param['data']);
            $param['updated_at'] = time();
            $flag = $good->editGoods($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);

        }
        $id = input('param.id');
        $class = db('class')->select();
        $type = db('goods_type')->select();
        $goods = $good->getOneGoods($id);
        $this->assign([
            'goods' => $goods,
            'class' => $class,
            'type' => $type,
        ]);
        return $this->fetch();
    }

    //删除角色
    public function goodsDel(){
        $id = input('param.goods_id');
        $good = new GoodsModel();
        $flag = $good->delGoods($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

}
