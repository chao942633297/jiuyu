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


use app\backsystem\model\ShopSpyRecordModel;
use app\backsystem\model\ShopSpySuccessModel;

class Shopspy extends Base
{
    // 商城窥探记录列表
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

   

            // if ($param['is_spy'] !== '') {
            //     $where['is_spy'] = $param['is_spy'];
            // }

            $shopgoods = new ShopSpySuccessModel();
            // $selectResult = $shopgoods->getshopgoodsByWhere($where, $offset, $limit,'is_spy , sort desc ,id desc');
            $selectResult = $shopgoods->getShopSuccessByWhere($where, $offset, $limit);

            foreach($selectResult as $key=>$vo){
                //窥探窥探记录操作按钮
                $selectResult[$key]['operate'] = $this->showOperate($this->makeButton($vo['id']));
                $selectResult[$key]['goodsimgurl'] = "<img src=".$vo['goodsimgurl']." width='120' />";
                $selectResult[$key]['is_spy'] = empty($vo['is_spy']) ? '抢购中奖' : '窥探中奖';

            }

            $return['total'] = $shopgoods->getAllshopSuccess($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        
        return $this->fetch();
    }

    // 添加商城窥探记录
    public function shopgoodsAdd()
    {
        if(request()->isPost()){
            $param = input('post.');

            unset($param['file']);
            // unset($param['is_inttime']);
            $param['created_at'] = date('Y-m-d H:i:s',time());

            $shopgoods = new ShopSpyRecordModel();
            $flag = $shopgoods->addshopgoods($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }
        //获取窥探记录分类信息
        $shopgoodsclass  =  model('ShopGoodsClassModel')->select();
        
        $this->assign('shopgoodsclass',$shopgoodsclass);
        return $this->fetch();
    }

    //窥探记录编辑
    public function shopgoodsEdit()
    {
        $shopgoods = new ShopSpyRecordModel();
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
        //获取窥探记录分类信息
        $shopgoodsclass  =  model('ShopGoodsClassModel')->select();
        
        $this->assign('shopgoodsclass',$shopgoodsclass);
        return $this->fetch();
    }

    //窥探记录删除
    public function shopgoodsDel()
    {
        $id = input('param.id');

        $shopgoods = new ShopSpyRecordModel();
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


    //窥探窥探记录添加
    public function shopspygoodsadd()
    {
        if(request()->isPost()){
            $param = input('post.');
            $param['sur_price'] = $param['price'];
            unset($param['file']);
            unset($param['is_inttime']);
            $param['created_at'] = date('Y-m-d H:i:s',time());

            $shopgoods = new ShopSpyRecordModel();
            $flag = $shopgoods->addshopgoods($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }
        //获取窥探记录分类信息
        $shopgoodsclass  =  model('ShopGoodsClassModel')->select();
        
        $this->assign('shopgoodsclass',$shopgoodsclass);
        return $this->fetch();
    }

    //窥探窥探记录编辑
    public function shopspygoodsedit()
    {
        $shopgoods = new ShopSpyRecordModel();
        if(request()->isPost()){

            $param = input('post.');
     
            unset($param['file']);
            unset($param['is_inttime']);
            $flag = $shopgoods->editshopgoods($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

        $id = input('param.id');
        $this->assign([
            'shopgoods' => $shopgoods->getOneshopgoods($id)
        ]);
        //获取窥探记录分类信息
        $shopgoodsclass  =  model('ShopGoodsClassModel')->select();
        $this->assign('shopgoodsclass',$shopgoodsclass);
        return $this->fetch();
    }




    /**
     * 拼装操作按钮
     * @param $id
     * @return array
     */
    private function makeButton($id)
    {
        return [
            '详情' => [
                'auth' => 'shopspy/spydetail',
                'href' => url('shopspy/spydetail', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '处理' => [
                'auth' => 'shopspy/shopspyedit',
                'href' => url('shopspy/shopspyedit', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            // '删除' => [
            //     'auth' => 'shopspy/shopspydel',
            //     'href' => "javascript:shopspyDel(" . $id . ")",
            //     'btnStyle' => 'danger',
            //     'icon' => 'fa fa-trash-o'
            // ]
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
