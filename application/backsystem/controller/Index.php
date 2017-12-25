<?php
namespace app\backsystem\controller;

class Index extends Base
{
    public function index()
    {
        return $this->fetch('/index');
    }

    /**
     * 后台默认首页
     * @return mixed
     */
    public function indexPage()
    {
        $month = getMonth();
        $day = getDay();
        $day_user = db('users')->whereTime('created_at','today')->count();  //今日新增会员量
//        $day_partner = db('users')->where(['created_at'=>['between',$day],'class'=>['>',1]])->count();  //今日新增合伙人量
        $all_user = db('users')->count();                                           //累计会员总量
        $yesDown = db('users')->group('pid')->count();         //有下级的会员统计
        $noDown = $all_user - $yesDown;   //没有下级的会员统计
        $all_money = db('order')->where(['status'=>['>',1]])->sum('price');
        $user_all_money = db('users')->sum('balance');                         //会员账户余额总计
        $day_order = db('order')->where(['status'=>['>',1]])->whereTime('created_at','today')->count();//今日订单统计
        $apply_day_partner = db('voucher')->whereTime('created_at','today')->count();   //今日申请合伙人统计
        $withdraw_true = db('withdraw')->where(['status'=>2])->sum('money');//已发放提现总额
        $day_withdraw_money = db('withdraw')->whereTime('created_at','today')->sum('money');//今日提现总额
        $day_withdraw_user_nums = db('withdraw')->whereTime('created_at','today')->count();//今日提现人数
        $day_withdraw_false = db('withdraw')->where(['status'=>3])->whereTime('created_at','today')->count();//今日已驳回提现
        $one_user = db('users')->where(['class'=>1])->count();//未消费会员
        $three_user = db('users')->where(['class'=>2])->count();//合伙人
        $four_user = db('users')->where(['class'=>['>',2]])->count();//报单中心
        $this->assign([
            'day_user'=>$day_user,
            'noDown'=>$noDown,
//            'day_partner'=>$day_partner,
            'all_user'=>$all_user,
            'all_money'=>$all_money,
            'day_order'=>$day_order,
            'user_all_money'=>$user_all_money,
            'apply_day_partner'=>$apply_day_partner,
            'withdraw_true'=>$withdraw_true,
            'day_withdraw_money'=>$day_withdraw_money,
            'day_withdraw_user_nums'=>$day_withdraw_user_nums,
            'day_withdraw_false'=>$day_withdraw_false,
            'one_user'=>$one_user,
            'three_user'=>$three_user,
            'four_user'=>$four_user,
        ]);
        return $this->fetch('index');
    }
}
