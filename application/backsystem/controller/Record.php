<?php

namespace app\backsystem\controller;

use app\backsystem\model\AccountModel;
use app\backsystem\model\UserModel;
use think\Controller;
use think\Request;

class Record extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     * 充值记录查询
     */
    public function recharge()
    {
        if(request()->isAjax()){
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = $whereu = $uids = [];
            $where['type'] = ['in',[7,8,10,11]];
            if (isset($param['truename']) && !empty($param['truename'])) {      //收货人查询
                $whereu['nickname'] = ['like','%'.$param['truename'].'%'];
            }
            if (isset($param['phone']) && !empty($param['phone'])) {      //手机号查询
                $whereu['phone'] = ['like','%'.$param['phone'].'%'];
            }
            $user = new UserModel();
            if(!empty($whereu)){
                $uids = $user->where($whereu)->column('id');
            }
            //支付类型
            if (isset($param['type']) && $param['type'] != '未选择') {
                $where['type'] = $param['type'];
            }
            //订单状态
            if (isset($param['status']) && $param['status']!= 0) {
                $where['inc'] = $param['status'];
            }
            //下单时间段
            //下单时间段
            if (isset($param['end']) && !empty($param['end']) && isset($param['start']) && !empty($param['start'])) {
                $time[1] = $param['end'].' 23:59:59';
                $time[0] = $param['start'].' 00:00:00';
                $where['create_at'] = ['between',$time];
            }

            $account = new AccountModel();
            if(isset($param['excel']) && $param['excel'] == 'to_excel'){
                $offset = 0;
                $limit = 9999;
            }
            $selectResult = $account->all(function($query)use($where,$offset,$limit,$uids){
                $query->where($where);
                if($uids){
                    $query->where('uid','in',$uids);
                }
                $query->limit($offset,$limit);
            });
            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['nickname'] = $vo['users']['nickname'];
                $selectResult[$key]['phone'] = $vo['users']['phone'];
                $selectResult[$key]['charge'] = config('account_inc')[$vo['inc']];
            }
            if(isset($param['excel']) && $param['excel'] == 'to_excel'){    //导出到excel
//                $content = json_decode(json_encode($selectResult),true);
                $content = [];
                foreach($selectResult as $k=>$v){
                    $content[$k]['id'] = $v['id'];
                    $content[$k]['nickname'] = $v['nickname'];
                    $content[$k]['phone'] = $v['phone'];
                    $content[$k]['balance'] = $v['balance'];
                    $content[$k]['remark'] = $v['remark'];
                    $content[$k]['charge'] = $v['charge'];
                    $content[$k]['create_at'] = $v['create_at'];
                }
                $excel = new Excel();
                $first = ['A1'=>'编号ID','B1'=>'用户名','C1'=>'手机号','D1'=>'充值金额','E1'=>'充值类型','F1'=>'增加/减少','G1'=>'充值时间'];
                $excel->toExcel('记录列表'.date('YmdHis'),$content,$first);
                return json(['code'=>1]);
            }
            $return['total'] = $account->where($where)->count();  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    /**
     *
     * @return \think\Response
     *
     *余额互转记录
     */
    public function transfer()
    {
        if(request()->isAjax()){
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = $whereu = $wheref = $uids = $fromids = [];
            $where['type'] = ['in',[4,11]];
            if(isset($param['direct']) &&  !empty($param['direct'])){
                $where['type'] = $param['direct'];
            }
            if (isset($param['out_phone']) && !empty($param['out_phone'])) {      //收货人查询
                $whereu['phone'] = ['like','%'.$param['out_phone'].'%'];
            }
            if (isset($param['into_phone']) && !empty($param['into_phone'])) {      //手机号查询
                $wheref['phone'] = ['like','%'.$param['into_phone'].'%'];
            }
            $user = new UserModel();
            if(!empty($whereu)){
                $uids = $user->where($whereu)->column('id');
            }

            if(!empty($wheref)){
                $fromids = $user->where($wheref)->column('id');
            }

            //下单时间段
            if (isset($param['end']) && !empty($param['end']) && isset($param['start']) && !empty($param['start'])) {
                $time[1] = $param['end'].' 23:59:59';
                $time[0] = $param['start'].' 00:00:00';
                $where['create_at'] = ['between',$time];
            }
            $account = new AccountModel();
            if(isset($param['excel']) && $param['excel'] == 'to_excel'){
                $offset = 0;
                $limit = 9999;
            }
            $selectResult = $account->all(function($query)use($where,$offset,$limit,$uids,$fromids){
                $query->order('id','desc');
                $query->where($where);
                if($uids){
                    $query->where('uid','in',$uids);
                }
                if($fromids){
                    $query->where('from_uid','in',$fromids);
                }
                $query->limit($offset,$limit);
            });
            foreach($selectResult as $key=>$vo){
                if($vo['inc'] == 1){
                    $selectResult[$key]['out_phone'] = $vo['from']['phone'];
                    $selectResult[$key]['into_phone'] = $vo['users']['phone'];
                }else{
                    $selectResult[$key]['out_phone'] = $vo['users']['phone'];
                    $selectResult[$key]['into_phone'] = $vo['from']['phone'];
                }
            }
            if(isset($param['excel']) && $param['excel'] == 'to_excel'){    //导出到excel
                $content = [];
                foreach($selectResult as $k=>$v){
                    $content[$k]['id'] = $v['id'];
                    $content[$k]['out_phone'] = $v['out_phone'];
                    $content[$k]['into_phone'] = $v['into_phone'];
                    $content[$k]['balance'] = $v['balance'];
                    $content[$k]['remark'] = $v['remark'];
                    $content[$k]['create_at'] = $v['create_at'];
                }
                $excel = new Excel();
                $first = ['A1'=>'编号ID','B1'=>'发起者账号','C1'=>'接收者账号','D1'=>'金额','E1'=>'类型','F1'=>'时间'];
                $excel->toExcel('记录列表'.date('YmdHis'),$content,$first);
                return json(['code'=>1]);
            }
            $return['total'] = $account->where($where)->count();  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    /**
     *
     * @param  \think\Request  $request
     * @return \think\Response
     * 返佣记录
     */
    public function rebate()
    {
        if(request()->isAjax()){
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = $whereu = $uids = [];
            $where['type']=['in',[1,2,9]];
            if (isset($param['truename']) && !empty($param['truename'])) {      //收货人查询
                $whereu['nickname'] = ['like','%'.$param['truename'].'%'];
            }
            if (isset($param['phone']) && !empty($param['phone'])) {      //手机号查询
                $whereu['phone'] = ['like','%'.$param['phone'].'%'];
            }
            $user = new UserModel();
            if(!empty($whereu)){
                $uids = $user->where($whereu)->column('id');
            }
            //支付类型
            if (isset($param['type']) && $param['type'] != '未选择') {
                $where['type'] = $param['type'];
            }
            //订单状态
            if (isset($param['status']) && $param['status'] != 0) {
                $where['inc'] = $param['status'];
            }
            //下单时间段
            //下单时间段
            if (isset($param['end']) && !empty($param['end']) && isset($param['start']) && !empty($param['start'])) {
                $time[1] = $param['end'].' 23:59:59';
                $time[0] = $param['start'].' 00:00:00';
                $where['create_at'] = ['between',$time];
            }
            $account = new AccountModel();
            if(isset($param['excel']) && $param['excel'] == 'to_excel'){
                $offset = 0;
                $limit = 9999;
            }
            $selectResult = $account->all(function($query)use($where,$offset,$limit,$uids){
                $query->where($where);
                if($uids){
                    $query->where('uid','in',$uids);
                }
                $query->limit($offset,$limit);
            });
            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['nickname'] = $vo['users']['nickname'];
                $selectResult[$key]['phone'] = $vo['users']['phone'];
                $selectResult[$key]['down_phone'] = $vo['from']['phone'];
                $selectResult[$key]['down_name'] = $vo['from']['nickname'];
                $selectResult[$key]['charge'] = config('account_inc')[$vo['inc']];
            }
            if(isset($param['excel']) && $param['excel'] == 'to_excel'){    //导出到excel
                $content = [];
                foreach($selectResult as $k=>$v){
                    $content[$k]['id'] = $v['id'];
                    $content[$k]['nickname'] = $v['nickname'];
                    $content[$k]['phone'] = $v['phone'];
                    $content[$k]['balance'] = $v['balance'];
                    $content[$k]['remark'] = $v['remark'];
                    $content[$k]['charge'] = $v['charge'];
                    $content[$k]['down_phone'] = $v['down_phone'];
                    $content[$k]['down_name'] = $v['down_name'];
                    $content[$k]['create_at'] = $v['create_at'];
                }
                $excel = new Excel();
                $first = ['A1'=>'编号ID','B1'=>'用户名','C1'=>'手机号','D1'=>'金额','E1'=>'原因','F1'=>'增加/减少','G1'=>'下级手机号','H1'=>'下级用户名','I1'=>'时间'];
                $excel->toExcel('记录列表'.date('YmdHis'),$content,$first);
                return json(['code'=>1]);
            }
            $return['total'] = $account->where($where)->count();  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }
        return $this->fetch();
    }



}
