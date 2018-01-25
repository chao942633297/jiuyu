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
use app\backsystem\controller\Excel;
use think\Db;

class Shopspy extends Base
{
    // 商城窥探记录列表
    public function list()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['starttime'])) {
                $where['created_at'] = ['>=',  $param['starttime'] ];
            }

            if (!empty($param['endtime'])) {
                $where['created_at'] = ['<=',  $param['endtime'] ];
            }

            if (!empty($param['username'])) {
                $where['username'] = ['like', '%' . $param['username'] . '%'];
            }

            if ($param['status'] != NULL) {
                $where['status'] = $param['status'];
            }


            $shopgoods = new ShopSpySuccessModel();
            // $selectResult = $shopgoods->getshopgoodsByWhere($where, $offset, $limit,'is_spy , sort desc ,id desc');
            $selectResult = $shopgoods->getShopSuccessByWhere($where, $offset, $limit);

            foreach($selectResult as $key=>$vo){
                //窥探窥探记录操作按钮
                $selectResult[$key]['operate'] = $this->showOperate($this->makeButton($vo['id']));
                // $userData = Db::name('users')->field('phone')->find($vo['userid']);
                // $selectResult[$key]['phone'] = $userData['phone'];

                // $selectResult[$key]['goodsimgurl'] = "<img src=".$vo['goodsimgurl']." width='120' />";
                // $selectResult[$key]['is_spy'] = empty($vo['is_spy']) ? '抢购中奖' : '窥探中奖';

            }

            $return['total'] = $shopgoods->getAllshopSuccess($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        
        return $this->fetch();
    }

    /*导出订单到excel
     *
     * @param   筛选条件
     */
    public function orderToExcel()
    {
        $param = input('param.');
        $where = [];
        if (!empty($param['starttime'])) {
            $where['created_at'] = ['>=',  $param['starttime'] ];
        }

        if (!empty($param['endtime'])) {
            $where['created_at'] = ['<=',  $param['endtime'] ];
        }

        if (!empty($param['username'])) {
            $where['username'] = ['like', '%' . $param['username'] . '%'];
        }

        if ($param['status'] != NULL) {
            $where['status'] = $param['status'];
        }


        $selectResult = Db::name('shop_spy_success')->where($where)->field('username,goodsname,goodsprice,times,created_at')->select();
        $selectResult = objToArray($selectResult);
        
        // $payment = ['1'=>'支付宝','2'=>'微信','3'=>'余额'];

        // foreach($selectResult as $key=>$vo){
        //     $selectResult[$key]['payment'] = $payment[$vo['payment']];
        // }

        $excel = new Excel();
        $first = ['用户名','商品','原价','中奖轮次','中奖时间'];
        array_unshift($selectResult,$first);
        $excel->exportExcel('获奖名单'.date('YmdHis'),$selectResult);
    }




    /** 中奖详情
     * @param  $id spy_success 主键
     *
     */
    public function spydetail()
    {
        $id = input('param.id');
        $successdata = Db::name('shop_spy_success')->find($id);
        if (request()->isPost()) {
            $remark = input('post.remark');
            if (empty(trim($remark))) {
                return json(['code'=>0, 'data'=>'', 'msg'=>'请填写处理意见！']);
            }
            $re = Db::name('shop_spy_success')->where('id',$id)->setfield(['remark'=>$remark,'status'=>1]);
            if ($re || ($successdata['remark'] == $remark)) {
                return json(['code'=>1, 'data'=>'', 'msg'=>'提交成功！']);
            }
            return json(['code'=>0, 'data'=>'', 'msg'=>'提交失败']);
        }

        $recorddata = Db::name('shop_spy_record')->where(['goodsid'=>$successdata['goodsid'],'times'=>$successdata['times']])->order('created_at','ASC')->select();
        $spyingdata = Db::name('shop_spying_goods')->where(['goodsid'=>$successdata['goodsid'],'times'=>$successdata['times']])->order('created_at','ASC')->select();
        
        $this->assign('successdata',$successdata);
        $this->assign('spyingdata',$spyingdata);
        $this->assign('recorddata',$recorddata);
        return $this->fetch();
    }

    

    
    /** 处理中奖记录
     *
     * 
     * 
     */
    public function shopspyedit()
    {
        
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
            '审核并处理' => [
                'auth' => 'shopspy/spydetail',
                'href' => url('shopspy/spydetail', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            // '处理' => [
            //     'auth' => 'shopspy/shopspyedit',
            //     'href' => url('shopspy/shopspyedit', ['id' => $id]),
            //     'btnStyle' => 'primary',
            //     'icon' => 'fa fa-paste'
            // ],
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
