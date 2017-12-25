<?php
/**
 * Created by PhpStorm.
 * User: ovo
 * Date: 2017/7/10
 * Time: 下午6:08
 */
namespace app\backsystem\controller;

use app\backsystem\model\GtypeModel;

class Gtype extends Base{
    const GOODS = 'goods';
    //用户列表
    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['class']) && !empty($param['class'])) {
                $where['class'] = ['like', '%' . $param['class'] . '%'];
            }

//            dump($where);exit;
            $gtype = new GtypeModel();
            $selectResult = $gtype->getTypesByWhere($where, $offset, $limit);


            foreach($selectResult as $key=>$vo){
                $is_goods = db(self::GOODS)->where(['cid'=>$vo['id']])->count();
                $selectResult[$key]['goods_num'] = $is_goods;
                $selectResult[$key]['remark'] = "<img src='".$vo['remark']."' width='50px' height='50px' />";

                if($is_goods){      //如果类别下存在商品则不能删除
                    $operate = [
                        '编辑' => url('gtype/gtypeEdit', ['id' => $vo['id']]),
                    ];
                }else{
                    $operate = [
                        '编辑' => url('gtype/gtypeEdit', ['id' => $vo['id']]),
                        '删除' => "javascript:gtypeDel('".$vo['id']."')"
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

    //添加用户
    public function gtypeAdd()
    {
        if(request()->isPost()){

            $param = input('param.');
            $param = parseParams($param['data']);

            $insert['class'] = $param['class'];
            $insert['remark'] = $param['remark'];
            if(isset($param['sort']) && !empty($param['sort'])){
                $insert['sort'] = $param['sort'];
            }
            $gtype = new GtypeModel();
            $flag = $gtype->insertType($insert);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        return $this->fetch();
    }

    //编辑角色
    public function gtypeEdit()
    {
        $gtype = new GtypeModel();

        if(request()->isPost()){

            $param = input('post.');
            $param = parseParams($param['data']);
            $save['id'] = $param['id'];
            $save['class'] = $param['class'];
            $save['remark'] = $param['remark'];
            if(isset($param['sort']) && !empty($param['sort'])){
                $save['sort'] = $param['sort'];
            }
            $flag = $gtype->editType($save);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');
        $this->assign([
            'gtype' => $gtype->getOneType($id),
        ]);
        return $this->fetch();
    }

    //删除角色
    public function gtypeDel()
    {
        $id = input('param.id');

        $gtype = new GtypeModel();
        $flag = $gtype->delType($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
}