<?php
/**
 * Created by PhpStorm.
 * User: ovo
 * Date: 2017/7/10
 * Time: 下午6:08
 */
namespace app\backsystem\controller;


use app\backsystem\model\CarTypeModel;

class Car extends Base{
    CONST GOODS = 'goods';
    //用户列表
    public function index()
    {
        if(request()->isAjax()){

            #分页属性
            $param  = input('param.');
            $limit  = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            #搜索条件
            if (isset($param['class']) && !empty($param['class'])) {
                $where['name'] = ['like', '%' . $param['class'] . '%'];
            }

            $gtype = new CarTypeModel();
            $selectResult = $gtype->getTypesByWhere($where, $offset, $limit);


            foreach($selectResult as $key=>$vo){
                $is_goods = db(self::GOODS)->where(['type_id'=>$vo['id']])->count();
                $selectResult[$key]['goods_num'] = $is_goods;
                if($is_goods){      //如果类别下存在商品则不能删除
                    $operate = [
                        '编辑' => url('Car/typeUpdate', ['id' => $vo['id']]),
                    ];
                }else{
                    $operate = [
                        '编辑' => url('Car/typeUpdate', ['id' => $vo['id']]),
                        '删除' => "javascript:typeDel('".$vo['id']."')"
                    ];
                }

                $selectResult[$key]['operate'] = showOperate($operate);

            }

            $return['total'] = $gtype->getAllTypes($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    //类别添加
    public function typeAdd()
    {
        if(request()->isPost()){

            $param = input('param.');
            $param = parseParams($param['data']);

            $insert['name'] = $param['class'];
            $insert['created_at'] = date('YmdHis');
            $gtype = new CarTypeModel();
            $flag = $gtype ->insertType($insert);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        return $this->fetch();
    }

    //编辑车系
    public function typeUpdate()
    {
        $gtype = new CarTypeModel();

        if(request()->isPost()){
            $param = input('post.');
            $param = parseParams($param['data']);
            $save['id'] = $param['id'];
            $save['name'] = $param['class'];
            $flag = $gtype->editType($save);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');
        $this->assign([
            'gtype' => $gtype->getOneType($id),
        ]);
        return $this->fetch();
    }

    //删除类别
    public function typeDel()
    {
        $id = input('param.id');

        $gtype = new CarTypeModel();
        $flag = $gtype->delType($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
}