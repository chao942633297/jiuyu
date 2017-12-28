<?php
// +----------------------------------------------------------------------
// | snake
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\backsystem\controller;


use app\backsystem\model\ShopGoodsModel;

class Shopgoods extends Base
{
    // 商城商品列表
    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['name'])) {
                $where['name'] = ['like', '%' . $param['name'] . '%'];
            }

            if (!empty($param['cid'])) {
                $where['cid'] = $param['cid'];
            }

            if ($param['is_under'] !== '') {
                $where['is_under'] = $param['is_under'];
            }

            $shopgoods = new ShopGoodsModel();
            // $selectResult = $shopgoods->getshopgoodsByWhere($where, $offset, $limit,'is_under , sort desc ,id desc');
            $selectResult = $shopgoods->getshopgoodsByWhere($where, $offset, $limit);

            foreach($selectResult as $key=>$vo){
                $re = model('ShopGoodsClassModel')->getOneShopGoodsClassField($vo['cid'],'classname');
                $selectResult[$key]['classname'] = $re['classname'];
                $selectResult[$key]['imgurl'] = '<img src="' . $vo['imgurl'] . '" width="40px" height="40px">';
                $selectResult[$key]['operate'] = $this->showOperate($this->makeButton($vo['id']));
                $selectResult[$key]['is_under'] = empty($vo['is_under']) ? '销售中' : '<font color="red">已下架</font>';

            }

            $return['total'] = $shopgoods->getAllshopgoods($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        $classList = model('ShopGoodsClassModel')->getShopGoodsClassList();
        $this->assign('classList',$classList);
        return $this->fetch();
    }

    // 添加商城商品
    public function shopgoodsAdd()
    {
        if(request()->isPost()){
            $param = input('post.');

            unset($param['file']);
            $param['created_at'] = date('Y-m-d H:i:s',time());

            $shopgoods = new ShopGoodsModel();
            $flag = $shopgoods->addshopgoods($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }
        //获取商品分类信息
        $shopgoodsclass  =  model('ShopGoodsClassModel')->select();
        
        $this->assign('shopgoodsclass',$shopgoodsclass);
        return $this->fetch();
    }

    //商品编辑
    public function shopgoodsEdit()
    {
        $shopgoods = new ShopGoodsModel();
        if(request()->isPost()){

            $param = input('post.');
            unset($param['file']);
            $flag = $shopgoods->editshopgoods($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

        $id = input('param.id');
        $this->assign([
            'shopgoods' => $shopgoods->getOneshopgoods($id)
        ]);
        //获取商品分类信息
        $shopgoodsclass  =  model('ShopGoodsClassModel')->select();
        
        $this->assign('shopgoodsclass',$shopgoodsclass);
        return $this->fetch();
    }

    //商品删除
    public function shopgoodsDel()
    {
        $id = input('param.id');

        $shopgoods = new ShopGoodsModel();
        $flag = $shopgoods->delshopgoods($id);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }

    // 上传缩略图
    public function uploadImg()
    {
        if(request()->isAjax()){

            $file = request()->file('file');
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' . DS . 'upload');
            if($info){
                $src =  '/upload' . '/' . date('Ymd') . '/' . $info->getFilename();
                return json(msg(0, ['src' => $src], ''));
            }else{
                // 上传失败获取错误信息
                return json(msg(-1, '', $file->getError()));
            }
        }
    }

    /**
     * 拼装操作按钮
     * @param $id
     * @return array
     */
    private function makeButton($id)
    {
        return [
            '编辑' => [
                'auth' => 'shopgoods/shopgoodsedit',
                'href' => url('shopgoods/shopgoodsedit', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '删除' => [
                'auth' => 'shopgoods/shopgoodsdel',
                'href' => "javascript:shopgoodsDel(" . $id . ")",
                'btnStyle' => 'danger',
                'icon' => 'fa fa-trash-o'
            ]
        ];
    }




    /**
     * 生成操作按钮
     * @param array $operate 操作按钮数组
     */
    function showOperate($operate = [])
    {
        if(empty($operate)){
            return '';
        }

        $option = '';
        foreach($operate as $key=>$vo){
            if(authCheck($vo['auth'])){
                $option .= ' <a href="' . $vo['href'] . '"><button type="button" class="btn btn-' . $vo['btnStyle'] . ' btn-sm">'.
                    '<i class="' . $vo['icon'] . '"></i> ' . $key . '</button></a>';
            }
        }

        return $option;
    }
}
