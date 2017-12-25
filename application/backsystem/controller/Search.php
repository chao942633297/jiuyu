<?php
namespace app\backsystem\controller;

use think\Controller;
use app\backsystem\controller\ExpressSelect;

class Search extends Controller{


    #查询快递
    public function expressSearch($express1){
        // #查询订单物流单号
        // $express_num = M('orders')->where(['id'=>I('get.id'),'user_id'=>$_SESSION['home']['user']['id']])->find();
        // #商品id
        // $goods_id = M('orders_detail')->where(['order_id'=>$express_num['id']])->getfield('good_id');
        // #商品主图
        // $main_pic = M('goods')->where(['id'=>$goods_id])->getfield('main_pic');
        // vendor('Express.ExpressSelect');
        $express = new ExpressSelect();
        $result  = $express -> getorder($express1);
        dump($result);die;
        // $this->assign('main_pic',$main_pic);
        // $this->assign('express',$result);
        // $this->assign('express_num',$express_num);
        $this->display();

    }
/*字段名称    　　字段含义

* com 物流公司编号
* nu  物流单号
* time    每条跟踪信息的时间
* context 每条跟综信息的描述
* state   快递单当前的状态 ：　 
* 0：在途，即货物处于运输过程中；
* 1：揽件，货物已由快递公司揽收并且产生了第一条跟踪信息；
* 2：疑难，货物寄送过程出了问题；
* 3：签收，收件人已签收；
* 4：退签，即货物由于用户拒签、超区等原因退回，而且发件人已经签收；
* 5：派件，即快递正在进行同城派件；
* 6：退回，货物正处于退回发件人的途中；
* 该状态还在不断完善中，若您有更多的参数需求，欢迎发邮件至 kuaidi@kingdee.com 提出。

status  查询结果状态： 
0：物流单暂无结果， 
1：查询成功， 
2：接口出现异常，
message 无意义，请忽略
condition   无意义，请忽略
ischeck 无意义，请忽略*/


}






