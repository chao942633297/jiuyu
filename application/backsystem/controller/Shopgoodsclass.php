<?php
// +----------------------------------------------------------------------
// | snake
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:
// +----------------------------------------------------------------------
namespace app\backsystem\controller;

use app\backsystem\model\ShopGoodsClassModel;

class Shopgoodsclass extends Base
{
    // 商品分类列表
    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['classname'])) {
                $where['classname'] = ['like', '%' . $param['classname'] . '%'];
            }

            $ShopGoodsClass = new ShopGoodsClassModel();
            $selectResult = $ShopGoodsClass->getShopGoodsClassByWhere($where, $offset, $limit);

            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['operate'] = $this->showOperate($this->makeButton($vo['id']));
            }

            $return['total'] = $ShopGoodsClass->getAllShopGoodsClass($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    // 添加商品分类
    public function ShopGoodsClassAdd()
    {
        if(request()->isPost()){
            $param = input('post.');

            $param['created_at'] = date('Y-m-d H:i:s');
            $param['sort'] = '1';

            $ShopGoodsClass = new ShopGoodsClassModel();
            $flag = $ShopGoodsClass->addShopGoodsClass($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

        return $this->fetch();
    }

    //商品分类 编辑
    public function ShopGoodsClassEdit()
    {
        $ShopGoodsClass = new ShopGoodsClassModel();
        if(request()->isPost()){

            $param = input('post.');
            unset($param['file']);
            $flag = $ShopGoodsClass->editShopGoodsClass($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

        $id = input('param.id');
        $this->assign([
            'ShopGoodsClass' => $ShopGoodsClass->getOneShopGoodsClass($id)
        ]);
        return $this->fetch();
    }

    //商品分类 删除
    public function ShopGoodsClassDel()
    {
        $id = input('param.id');
        
        $ShopGoodsClass = new ShopGoodsClassModel();
        $flag = $ShopGoodsClass->delShopGoodsClass($id);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
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
                'auth' => 'shopgoodsclass/shopgoodsclassedit',
                'href' => url('shopgoodsclass/shopgoodsclassedit', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '删除' => [
                'auth' => 'shopgoodsclass/shopgoodsclassdel',
                'href' => "javascript:ShopGoodsClassDel(" . $id . ")",
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
