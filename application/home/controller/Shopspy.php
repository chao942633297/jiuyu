<?php
namespace app\home\controller;

use app\backsystem\model\UserModel;
use app\backsystem\model\ShopGoodsModel;
use app\backsystem\model\ShopSpyRecordModel;
use app\backsystem\model\ShopSpySuccessModel;
use app\backsystem\model\AccountModel;

use think\Controller;
use think\Validate;
use think\Session;
use think\Db;
use app\home\Alipay;

/**
 * 窥探商品
 */
class Shopspy extends Controller
{
    // 展示窥探商品列表
    public function shopGoodsList()
    {
        $shopgoods = new ShopGoodsModel();
        //根据sort 获取销售中（is_under=0）的窥探商品
        $page = !empty(input('param.page')) ? input('param.page') : '1';
        $limit = !empty(input('param.limit')) ? input('param.limit') : '10';
        $offset = ($page - 1) * $limit;
        $where = array();
        $where['is_under'] = '0';
        $where['is_delete'] = '0';
        $where['cid'] = '2'; //只获取窥探商品
        $where['status'] = '0'; //0:正常 1：中奖间隔期


        // 返回列表之前先检测和更新需要重新上架的窥探商品
        if (Db::name('shop_goods')->where(['status' => '1'])->count()) {
            $reData = Db::name('shop_goods')->where(['status' => '1'])->select();
            foreach ($reData as $key => $value) {
                if (((time() - strtotime($value['last_wintime'])) / 3600) > $value['she_timeint']) {
                    $setData = [];
                    $setData['sur_price'] = $value['price'];
                    $setData['spy_price'] = '0';
                    $setData['status'] = '0';
                    $setData['times'] = $value['times'] + 1;  // 中奖成功后 不+1  重新上架后+1
                    Db::name('shop_goods')->where('id', $value['id'])->update($setData);
                }
            }
        }

        $shopGoodsList = $shopgoods->getShopGoodsByWhere($where, $offset, $limit, 'id desc', 'id,name,cid,unit,imgurl,remark,description,canshu,once_price,int_time,countdown,hot');

        return json(['code' => 1, 'data' => $shopGoodsList, 'msg' => 'success']);

    }

    //全部中奖列表
    public function wonlist()
    {
        $page = !empty(input('param.page')) ? input('param.page') : '1';
        $limit = !empty(input('param.limit')) ? input('param.limit') : '10';
        $offset = ($page - 1) * $limit;
        $listData = Db::name('shop_spy_success')->field("id,username,goodsname,goodsid,created_at")->limit($offset, $limit)->order("id DESC")->select();
        return json(['code' => 1, 'data' => $listData, 'msg' => 'success']);
    }

    //个人中奖记录
    public function myWonlist()
    {
        $userid = session('home_user_id');
        if (empty($userid) || ($userid < 0)) {
            return json(['code' => 0, 'data' => '', 'msg' => '请先登录！']);
        }

        $page = !empty(input('param.page')) ? input('param.page') : '1';
        $limit = !empty(input('param.limit')) ? input('param.limit') : '10';
        $offset = ($page - 1) * $limit;
        // $listData = Db::name('shop_spy_success')->field("id,username,goodsname,goodsimgurl,sur_price,goodsid,created_at")->where(['userid' => $userid])->limit($offset, $limit)->order("id DESC")->select();

        $listData = Db::name('shop_spy_success')->field("id,username,goodsname,goodsimgurl,sur_price,goodsid,created_at")->where(['userid' => $userid,'is_spy'=>1])->limit($offset, $limit)->order("id DESC")->select(); //不包含倒计时走完中奖的
        return json(['code' => 1, 'data' => $listData, 'msg' => 'success']);
    }

    /*获取窥探商品详情
     * @param id 商品id
     *
     */
    public function shopGoodsInfo()
    {
        $id = !empty(input('param.id')) && input('param.id') > 0 ? input('param.id') : exit(json_encode(['code' => 0, 'data' => '', 'msg' => '参数异常']));
        $goodsInfo = Db::name('shop_goods')->where('id', $id)->field("id,name,cid,unit,imgurl,remark,description,canshu,once_price,int_time,countdown,hot")->find();
        if ($goodsInfo['cid'] != '2') {
            return json(['code' => 0, 'data' => '', 'msg' => '非窥探商品不予显示']);
        }

        if (empty(session('home_user_id'))) {
            $goodsInfo['balance'] = '0.00';
        } else {
            $user = UserModel::get(session('home_user_id'));
            $goodsInfo['balance'] = $user->balance;
        }
        // $shopgoods = new ShopGoodsModel();
        // $goodsInfo = $shopgoods->getOneShopGoods($id,"id,name,cid,unit,imgurl,remark,description,canshu,once_price,int_time,countdown,hot");
        return json(['code' => 1, 'data' => $goodsInfo, 'msg' => 'success']);
    }


    /** 窥探支付
     *
     *
     *
     */
    public function spyadd()
    {
        $userid = session('home_user_id');
        if (empty($userid) || ($userid < 0)) {
            return json(['code' => 0, 'data' => '', 'msg' => '请先登录！']);
        }

        $rule = [
            'goodsid' => 'require',
            'spy_num' => 'require',
            'payment' => 'require',
            // 'two_password' => 'require',
        ];
        $msg = [
            'goodsid.require' => '商品id不能空',
            'spy_num' => '窥探次数不能空',
            'payment' => '支付方式不能空',
            // 'two_password' => '支付密码不能空',
        ];

        if (empty($_POST['two_password'])) {
            $_POST['two_password'] = '111111';
            $_POST['goodsid'] = '47';
            $_POST['spy_num'] = '1';
            $_POST['payment'] = '3';
        }

        $input = input('post.');
        $validate = new Validate($rule, $msg);
        if (!$validate->check($input)) {
            return json(['msg' => $validate->getError(), 'code' => 0]);
        }
        if ($input['spy_num'] < 1 || $input['spy_num'] > 10) {
            return json(['code' => 0, 'data' => '', 'msg' => '窥探次数必须是1-10次之间']);
        }

        // 验证支付密码
        $user = UserModel::get($userid);
        //验证支付密码是否正确  微信支付宝不用输入二级密码
        if ($input['payment'] == 3) {
            if($user->two_password !== md5($input['two_password'])){
                return json(['msg'=>'支付密码不正确','code'=>0]);
            }
        }


        $goodsInfo = Db::name('shop_goods')->where('id', $input['goodsid'])->field('*')->find();

        if ($goodsInfo['is_under'] == '1' || empty($goodsInfo)) {
            return json(['code' => 0, 'data' => '', 'msg' => $goodsInfo['name'].'商品已经下架,请选择其他商品窥探！']);
        }
        if ($goodsInfo['sur_price'] == '0') {
            return json(['code' => 0, 'data' => '', 'msg' => $goodsInfo['name'].'商品已经被别人窥探走了,请选择其他商品窥探！']);
        }
        if (!in_array($input['payment'], array('1', '2', '3'))) {
            return json(['code' => 0, 'data' => '', 'msg' => '支付方式错误']);
        }


        //检查 本轮次 抢购中的订单 是否倒计时已过完 过完这表示中奖
        $r = $this->checkSpy($input['goodsid'],$goodsInfo['times']);
        if ($r == 1) {
            return json(['code' => 0, 'data' => '', 'msg' => '商品已经被别人抢走了']);
        }

        if (($input['payment'] == 3) && $user->balance < ($goodsInfo['once_price'] * $input['spy_num'])) {
            return json(['code' => 0, 'data' => '', 'msg' => '余额不足']);
        }

        //检查自己本轮次是否在抢购中  抢购中的用户 不能窥探
        if (Db::name('shop_spying_goods')->where(['userid' => $userid, 'goodsid' => $input['goodsid'], 'times' => $goodsInfo['times'], 'status' => '1'])->count()) {
            return json(['code' => 0, 'data' => '', 'msg' => '您已经抢购了此商品，不能继续窥探！']);
        }


        //余额支付
        $ShopSpy = new ShopSpyRecordModel();
        $lastRecord = $ShopSpy->getLastSpyRecords($userid, $goodsInfo['id']);
        $lastRecord = objToArray($lastRecord);

        if (!empty($lastRecord) && time() - strtotime($lastRecord[0]['created_at']) < $goodsInfo['int_time']) {
            return json(['code' => 0, 'data' => '', 'msg' => "窥探间隔时间".$goodsInfo['int_time']."秒，请稍后重试"]);
        }

        $insertData['userid'] = $userid;
        $insertData['username'] = $user['nickname'];
        $insertData['once_price'] = $goodsInfo['once_price'];
        $insertData['spy_num'] = $input['spy_num'];
        $insertData['amount'] = $insertData['once_price']*$insertData['spy_num'];
        $insertData['sur_price'] = $goodsInfo['sur_price']-$insertData['amount']; // 窥探后剩余价格
        $insertData['goodsid'] = $goodsInfo['id'];
        $insertData['goodsname'] = $goodsInfo['name'];
        $insertData['goodsimgurl'] = $goodsInfo['imgurl'];
        $insertData['times'] = $goodsInfo['times'];
        $insertData['payment'] = $input['payment'];
        $insertData['created_at'] = date("Y-m-d H:i:s");
        $insertData['status'] = '1';
        $insertData['spy_sn'] = $this->getSpyRecordSn();

        if ($input['payment'] == 3) {
            Db::startTrans();
            try {
                // 直接扣除余额 生成窥探记录   是否中奖  中奖后抢购订单变更为失败并返钱 并更新商品信息进入中奖期
                $accountId = $this->accountAdd($userid, $insertData['amount'], '2', '13', $remark = "窥探消费");
                $insertData['status'] = '2';
                $insertData['paymoney'] = $insertData['amount'];
                Db::name('shop_spy_record')->insert($insertData);
                Db::name('shop_goods')->where('id', $input['goodsid'])->dec('sur_price', $insertData['amount'])->inc('spy_price', $insertData['amount'])->inc('spy_amount', $insertData['amount'])->update();
                // dump(Db::name('shop_goods')->getLastSql());
                $newPrice = Db::name('shop_goods')->field('sur_price')->where('id',$goodsInfo['id'])->find();
                // dump(Db::name('shop_goods')->getLastSql());
                // dump($newPrice);
                // exit;
                $data = [];
                $data['sur_price'] = $newPrice['sur_price'];
                $data['balance'] = $user->balance;
                if (($insertData['sur_price'] / $goodsInfo['price'] * 10) < 3) {
                    $data['sur_price'] = "价格低于30%不予显示";
                }
                $code = 1; 
                $msg = '窥探成功';
                if ($insertData['sur_price'] < 1) {
                    $this->successAdd($userid, $input['goodsid'], $input['payment'], $insertData['amount'], '1', '0');
                    $this->setSpyingFail($input['goodsid'], $goodsInfo['times']);
                    Db::name('shop_goods')->where('id', $input['goodsid'])->inc('hot')->inc('realhot')->update();
                    // status  0： 正常  1：进入间隔期
                    Db::name('shop_goods')->where('id', $input['goodsid'])->setField(['last_wintime' => date("Y-m-d H:i:s"), 'status' => '1']);
                    $code = 100;  // 窥探成功code返回 100： msg返回商品名称
                    $msg = $goodsInfo['name'];
                }
                Db::commit();
                return json(['code' => $code, 'data' => $data, 'msg' => $msg]);
            } catch (\Exception $e) {
                Db::rollback();
                return json(['code' => -2, 'data' => '', 'msg' => $e->getMessage()]);
            }
            // return json(['code' => $code, 'data' => $data, 'msg' => $msg]);
        } else if ($input['payment'] == 1) {
            $id = Db::name('shop_spy_record')->insertGetid($insertData);
            if ($id) {
                $url = config('back_domain').'/home/alipay/webSpyPay?orderId='.$id;
                return json(['msg' => '', 'code' => 1, 'data' => $url]);
            } else {
                return json(['msg' => '发起支付失败', 'code' => 0, 'data' => '']);
            }
        } else if ($input['payment'] == 2) {
            $id = Db::name('shop_spy_record')->insertGetid($insertData);
            if ($id) {
                $url = config('back_domain').'/home/wxpay/webSpyPay?orderId='.$id;
                return json(['msg' => '', 'code' => 1, 'data' => $url]);
            } else {
                return json(['msg' => '发起支付失败', 'code' => 0, 'data' => '']);
            }
        } else {
            return json(['code' => 0, 'data' => '', 'msg' => '支付方式错误']);
        }


    }


    /** 抢购支付 生成抢购订单
     *
     *
     *
     */
    public function panicPay()
    {
        $userid = session('home_user_id');
        if (empty($userid) || ($userid < 0)) {
            return json(['code' => 0, 'data' => '', 'msg' => '请先登录！']);
        }
        $rule = [
            'goodsid' => 'require',
            'payment' => 'require',
            // 'two_password' => 'require',
            'province' => 'require',
            'city' => 'require',
            'area' => 'require',
            'detail' => 'require',
            'buyer_name' => 'require',
            'buyer_phone' => 'require',
        ];
        $msg = [
            'goodsid.require' => '商品id不能空',
            'payment' => '支付方式不能空',
            // 'two_password' => '支付密码不能空',
            'province' => '请填写省',
            'city' => '请填写市',
            'area' => '请填写区',
            'detail' => '请填写详细地址',
            'buyer_name' => '请填写收货人姓名',
            'buyer_phone' => '请填写收货人联系电话',
        ];

        // $_POST['two_password'] = '111111';
        // $_POST['goodsid'] = '47';
        // $_POST['payment'] = '2';
        // $_POST['province'] = '河南省';
        // $_POST['city'] = '郑州市';
        // $_POST['area'] = '高新区';
        // $_POST['detail'] = '莲花街工大36号';
        // $_POST['buyer_name'] = '王先生';
        // $_POST['buyer_phone'] = '18623695465';

        $input = input('post.');
        $validate = new Validate($rule, $msg);
        if (!$validate->check($input)) {
            return json(['msg' => $validate->getError(), 'code' => 0]);
        }

        // 验证支付密码
        $user = UserModel::get($userid);
        if ($input['payment'] == 3) {
            if ($user['two_password'] !== md5($input['two_password'])) {
                return json(['msg' => '支付密码不正确', 'code' => 0, 'data' => '']);
            }
        }
        


        $goodsInfo = Db::name('shop_goods')->where('id', $input['goodsid'])->field('*')->find();

        if ($goodsInfo['is_under'] == '1' || empty($goodsInfo)) {
            return json(['code' => 0, 'data' => '', 'msg' => $goodsInfo['name'].'商品已经下架,请选择其他商品抢购！']);
        }
        if ($goodsInfo['sur_price'] <= '0') {
            return json(['code' => 0, 'data' => '', 'msg' => $goodsInfo['name'].'商品已经被别人窥探走了,请选择其他商品抢购！']);
        }
        if (!in_array($input['payment'], array('1', '2', '3'))) {
            return json(['code' => 0, 'data' => '', 'msg' => '支付方式错误']);
        }
        //检查 本轮次 抢购中的订单 是否倒计时已过完 过完这表示中奖
        $r = $this->checkSpy($input['goodsid'],$goodsInfo['times']);
        if ($r == 1) {
            return json(['code' => 0, 'data' => '', 'msg' => '商品已经被别人抢走了']);
        }

        //自己查询窥探后的价格 防止抢购前别人又窥探导致价格变动
        $recordData = Db::name('shop_spy_record')->where(['goodsid' => $input['goodsid'], 'userid' => $userid])->order('id DESC')->limit('1')->find();
        if (empty($recordData['sur_price'])) {
            //窥探记录无法保存到数据库
            return json(['code' => 0, 'data' => '', 'msg' => '窥探失败']);
        }
        //抢购前商品前 检测 本轮次是否已经被别人 窥探到0
        $sData = Db::name('shop_spy_success')->where(['goodsid'=>$recordData['goodsid'],'times'=>$recordData['times']])->find();
        if (!empty($sData)) {
            return json(['code' => 0, 'data' => '', 'msg' => '商品已经被别人窥探走了']);
        }
        //判断余额
        if (($input['payment'] == 3) && ($user->balance < $recordData['sur_price'])) {
            return json(['code' => 0, 'data' => '', 'msg' => '余额不足']);
        }
        

        //检查自己本轮次是否在抢购中 防止重复生成抢购订单
        if (Db::name('shop_spying_goods')->where(['userid' => $userid, 'goodsid' => $input['goodsid'], 'times' => $recordData['times'], 'status' => '1'])->count()) {
            return json(['code' => 0, 'data' => '', 'msg' => '订单已经生成，请勿重复下单']);
        }

        $insertData['userid'] = $userid;
        $insertData['username'] = $user['nickname'];
        $insertData['sur_price'] = $recordData['sur_price'];
        $insertData['times'] = $recordData['times'];
        $insertData['goodsid'] = $input['goodsid'];
        $insertData['goodsname'] = $goodsInfo['name'];
        $insertData['goodsimgurl'] = $goodsInfo['imgurl'];
        $insertData['payment'] = $input['payment'];
        $insertData['buyer_name'] = $input['buyer_name'];
        $insertData['buyer_phone'] = $input['buyer_phone'];
        $insertData['province'] = $input['province'];
        $insertData['city'] = $input['city'];
        $insertData['area'] = $input['area'];
        $insertData['detail'] = $input['detail'];
        $insertData['paystatus'] = '1';
        $insertData['created_at'] = date('Y-m-d H:i:s');
        $insertData['spy_sn'] = $this->getSpySn();  //生成抢购单号

        //余额支付
        $ShopSpy = new ShopSpyRecordModel();
        if ($input['payment'] == 3) {
            Db::startTrans();
            try{
                // 扣除用户余额并产生一条消费记录
                // 扣除用户余额
                $this->accountAdd($userid, $insertData['sur_price'], '2', '13', $remark = "窥探消费");
                // 更新其他参与本商品本轮次状态为抢购中记录为失败 status=2  并将抢购失败用户金额返还到余额 并增加消费记录
                $this->setSpyingFail($insertData['goodsid'], $recordData['times']);
                
                // 添加新的抢购记录
                $insertData['paystatus'] = '2'; 
                $insertData['money'] = $insertData['sur_price'];  
                Db::name('shop_spying_goods')->insert($insertData);
                $data = [];
                $data['endtime'] = date("Y-m-d H:i:s", $goodsInfo['countdown'] * 3600 + time());
                Db::commit();
                return json(['code' => 1, 'data' => $data, 'msg' =>'抢购订单生成成功']);
            }catch(\Exception $e){
                Db::rollback();
                return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
            }
        } else if ($input['payment'] == 1) {
            $spyingId = Db::name('shop_spying_goods')->insertGetid($insertData);
            $url = config('back_domain').'/home/alipay/webSpyingPay?orderId='.$spyingId;
            return json(['msg' => '', 'code' => 1, 'data' => $url]);
        } else if ($input['payment'] == 2) {
            $spyingId = Db::name('shop_spying_goods')->insertGetid($insertData);
            $url = config('back_domain').'/home/Wxpay/wechatSpyingPay?orderId='.$spyingId;
            return json(['msg'=>'','code'=>1,'data'=>$url]);
        } else {
            return json(['code' => $flag['code'], 'data' => '', 'msg' => '支付方式错误']);
        }

        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);

    }

    /**
     * 获取 窥探商品最后一次窥探剩余价格
     *
     *
     */
    public function getLast()
    {
        $userid = session('home_user_id');
        if (empty($userid) || ($userid < 0)) {
            return json(['code' => -1, 'data' => '', 'msg' => '请先登录！']);
        }
        $code = 1;
        $msg = 'success';
        $goodsid = input('param.id');
        if (empty($goodsid) || $goodsid<0) {
            return json(['code' => 0, 'data' => '', 'msg' => '参数异常']);
        }
        $goodsInfo = Db::name('shop_goods')->where('id', $goodsid)->field("id,name,cid,unit,imgurl,remark,description,canshu,once_price,int_time,countdown,hot,times,price")->find();
        if ($goodsInfo['cid'] != '2') {
            return json(['code' => 0, 'data' => '', 'msg' => '非窥探商品不予显示']);
        }

        //本轮次 最后一次窥探价格
        $lastData = Db::name('shop_spy_record')->where(['goodsid' => $goodsid, 'userid' => $userid])->order('id DESC')->find();
        if ($goodsInfo['times'] != $lastData['times']) {
            $code = 2;
            $msg = '本次窥探奖品已经被别人窥探走了';
        }

        $goodsInfo['last_price'] = $lastData['sur_price'];

        if (($lastData['sur_price'] / $goodsInfo['price'] * 10) < 3) {
            $code = 3;
            $goodsInfo['last_price'] = '';
            $msg = "价格低于30%不予显示";
        }


        if ($lastData['sur_price'] < 1) {
            Db::startTrans();
            try{
                $this->successAdd($userid, $goodsid, $lastData['payment'], $lastData['amount'], '1', '0');
                $this->setSpyingFail($goodsid, $goodsInfo['times']);
                Db::name('shop_goods')->where('id', $goodsid)->inc('hot')->inc('realhot')->update();
                // status  0： 正常  1：进入间隔期
                Db::name('shop_goods')->where('id', $goodsid)->setField(['last_wintime' => date("Y-m-d H:i:s"), 'status' => '1']);
                Db::commit(); 
                $code = 100;  // 窥探成功code返回 100： msg返回商品名称
                $msg = $goodsInfo['name'];
            } catch (\Exception $e) {
                return json(['code' => -2, 'data' => '', 'msg' => $e->getMessage()]);
            }

        }
        unset($goodsInfo['price']);
        //用户余额
        $user = Db::name('users')->find($userid);
        $goodsInfo['balance'] = !empty($user['balance']) ? $user['balance'] : '0';

        return json(['code' => $code, 'data' => $goodsInfo, 'msg' => $msg]);
    }

    /*检测 抢购表中 是否有倒计时走完的订单
     *
     * @param goodsid 商品id
     * @param times   轮次
     * return 0 没有  1 存在并生成成功纪录存在success表中
     */
    public function checkSpy($goodsid,$times)
    {
        //检查 本轮次 抢购中的订单 是否倒计时已过完 过完这表示中奖
        $num = Db::name('shop_spying_goods')->where(['goodsid' => $goodsid, 'times' => $times, 'status' => '1'])->count();
        if ($num <= 0 ) {
            return 0;
        }else{
            $rt = 0;
            Db::startTrans();
            try {
                $goodsInfo = Db::name('shop_goods')->find($goodsid);
                $exdata = Db::name('shop_spying_goods')->where(['goodsid' => $goodsid, 'times' => $times, 'status' => '1'])->find();
                if ((time() - strtotime($exdata['created_at'])) / 3600 > $goodsInfo['countdown']) {
                    // 将本订单更新为status=3  向spy_success插入一条数据
                    Db::name('shop_spying_goods')->where(['id' => $exdata['id']])->setField('status', '3');
                    $successData = [];
                    $successData['goodsid'] = $goodsid;
                    $successData['goodsname'] = $goodsInfo['name'];
                    $successData['goodsprice'] = $goodsInfo['price'];
                    $successData['goodsimgurl'] = $goodsInfo['imgurl'];
                    $successData['goodscanshu'] = $goodsInfo['canshu'];
                    $successData['sur_price'] = $exdata['sur_price'];
                    $successData['once_price'] = $goodsInfo['once_price'];
                    $successData['is_spy'] = '0'; // 是否是窥探中奖 0:不是（抢购中奖）1：窥探中奖
                    $successData['userid'] = $exdata['userid'];
                    $successData['payment'] = $exdata['payment'];
                    $successData['times'] = $times;
                    $successData['last_amount'] = $exdata['sur_price'];
                    $successData['created_at'] = date("Y-m-d H:i:s");
                    Db::name('shop_spy_success')->insert($successData);

                    // 重置商品 信息  last_wintime  sur_price status 1:中奖间隔 0：正常售卖
                    Db::name('shop_goods')->where(['id' => $goodsid])->setInc('spy_amount', $exdata['sur_price']);
                    Db::name('shop_goods')->where(['id' => $goodsid])->update(['sur_price' => '0', 'last_wintime' => $successData['created_at'], 'status' => '1']);
                    $rt = 1;
                }
                Db::commit();
                return $rt;
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return -1;
            }
            
        }

    }


    /** 增加消费用户记录
     * $userid,
     * $amount, 变动金额
     * $inset,  加减   1：增加  2：减少
     * $type   消费类型   12：商城消费    13：窥探消费
     *
     * return 返回 记录id
     */
    public function accountAdd($userid, $amount, $inset, $type, $remark = "窥探消费")
    {
        Db::startTrans();
        try {
            if ($inset == '1') {
                Db::name('users')->where('id',$userid)->setInc('balance', $amount);
            } else if ($inset == '2') {
                Db::name('users')->where('id',$userid)->setDec('balance', $amount);
            }
            // 增加用户余额消费记录
            // $user = UserModel::get($param['userid']);
            $accountData = [];
            $accountData['uid'] = $userid;
            $accountData['balance'] = $amount; //账户扣除后的余额
            $accountData['remark'] = '窥探抢购消费';
            $accountData['inc'] = $inset;     // 1增加 2 减少
            $accountData['type'] = $type;  // 扣币类型 13：窥探消费
            $accountData['create_at'] = date('Y-m-d H:i:s');
            Db::name('account')->insertGetid($accountData);
            Db::commit();
            return 1;
        } catch (\Exception $e) {
            Db::rollback();
            return -1;
        }

    }


    /** 增加窥探成功记录
     * $userid,
     * $goodsid
     * $payment   1：支付宝  2：微信  3：余额
     *  $is_spy   1:窥探成功 0：抢购成功
     *  $last_amount  最后一次支付价格
     *  $spyingid   抢购订单id
     * return 返回 记录id
     */
    public function successAdd($userid, $goodsid, $payment, $last_amount, $is_spy, $spyingid='0')
    {
        Db::startTrans();
        try {
            $goods = new ShopGoodsModel();
            $goodsInfo = $goods->getOneShopGoods($goodsid);
            $successData = [];
            $successData['is_spy'] = $is_spy;
            $successData['spyingid'] = $spyingid;
            $successData['last_amount'] = $last_amount;
            $successData['goodsid'] = $goodsInfo['id'];
            $successData['goodsname'] = $goodsInfo['name'];
            $successData['goodsprice'] = $goodsInfo['price'];
            $successData['goodsimgurl'] = $goodsInfo['imgurl'];
            $successData['goodscanshu'] = $goodsInfo['canshu'];
            $successData['times'] = $goodsInfo['times'];
            $successData['sur_price'] = $goodsInfo['sur_price'];
            $successData['once_price'] = $goodsInfo['once_price'];
            $user = UserModel::get($userid);
            $successData['userid'] = $userid;
            $successData['username'] = $user->nickname;
            $successData['usermobile'] = $user->phone;
            $successData['payment'] = $payment;
            $successData['created_at'] = date("Y-m-d H:i:s");
            $id = Db::name('shop_spy_success')->insertGetid($successData);
            if ($id) {
                Db::commit();
                return $id;
            } else {
                Db::rollback();
                return -2;
            }
        } catch (\Exception $e) {
            Db::rollback();
            return -1;
        }

    }


    /** 将指定商品 所有抢购中的订单都设置为 失败 status=2  若抢购为余额支付 则将抢购失败用户金额返还到余额
     *
     *
     *
     * $payment  1:支付宝 2：微信 3：余额
     *
     *
     * return 1 :成功  <0 :失败
     */
    public function setSpyingFail($goodsid, $times)
    {
        // 更新其他参与本商品本轮次状态为抢购中记录为失败 status=2  并将抢购失败用户金额返还到余额
        $num = Db::name('shop_spying_goods')->where(['goodsid' => $goodsid, 'times' => $times, 'status' => '1'])->count();
        if ($num <= 0) {
            return 1;  //没有抢购中的订单
        }
        $faildata = Db::name('shop_spying_goods')->where(['goodsid' => $goodsid, 'times' => $times, 'status' => '1'])->select();//理论上应该只有一条记录
        $goodsInfo = Db::name('shop_goods')->field('once_price')->find($goodsid);//抢购包含一次窥探功能 返钱时少返 一个单位
        // echo "222222222222";exit;
        Db::startTrans();
        try {
            foreach ($faildata as $k => $v) {
                $r = Db::name('shop_spying_goods')->where(['userid' => $v['userid'], 'goodsid' => $goodsid, 'times' => $times, 'status' => '1'])->setField('status', '2');
                if ($r) {
                    $amount = $v['sur_price'] - $goodsInfo['once_price'];
                    $acId = $this->accountAdd($v['userid'], $amount, '1', '13', $remark = "窥探抢购失败返还");
                    if ($acId < 0) {
                        Db::rollback();
                        return -4;
                    }
                } else {
                    Db::rollback();
                    return -3;
                }
            }
            Db::commit();
            return 1;
        } catch (\Exception $e) {
            Db::rollback();
            return -2;
        }
    }

    /**
     * 随机生成 抢购号
     */
    public function getSpySn()
    {
        do {
            $num = date('YmdHis').rand(1000, 9999);
        } while (Db::name('shop_spying_goods')->where(['spy_sn' => $num])->find());
        return $num;
    }


    /**
     * 随机生成 抢购号
     */
    public function getSpyRecordSn()
    {
        do {
            $num = date('YmdHis').rand(10000, 99999);
        } while (Db::name('shop_spy_record')->where(['spy_sn' => $num])->find());
        return $num;
    }


}