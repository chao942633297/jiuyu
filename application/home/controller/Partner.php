<?php

namespace app\home\controller;

use app\backsystem\controller\File;
use app\backsystem\model\AccountModel;
use app\backsystem\model\AddressModel;
use app\backsystem\model\ApplyModel;
use app\backsystem\model\UserModel;
use app\backsystem\model\VoucherModel;
use Service\MsgCode;
use think\Controller;
use think\Db;
use think\Exception;
use think\Loader;
use think\Request;

class Partner extends Base
{
    protected $userId;

    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->userId = session('home_user_id');
    }


    /**
     * @return \think\response\Json
     * 订单激活列表
     */
    public function memberList()
    {
        $lists = VoucherModel::all(function ($query) {
            $query->field('id,uid,created_at');
            $query->where(['actid' => $this->userId, 'status' => 1]);
            $query->order('id', 'desc');
        });
        $return = [];
        $key = 0;
        foreach ($lists as $key => $val) {
            $return[$key]['id'] = $val['id'];
            $return[$key]['user_name'] = $val['user']['nickname'];
            $return[$key]['user_phone'] = $val['user']['phone'];
            $return[$key]['user_headimgurl'] = $val['user']['headimgurl'];
            $return[$key]['created_at'] = $val['created_at'];
        }
        $totalNum = $key + 1;
        return json(['data' => $return, 'totalNum' => $totalNum, 'msg' => '查询成功', 'code' => 200]);
    }


    /**
     * @param Request $request
     * @return \think\response\Json
     * 确认激活页面
     */
    public function sureActivate(Request $request)
    {
        $voucherId = $request->param('voucherId');
        $voucherData = VoucherModel::get($voucherId);
        if (empty($voucherData) && empty($voucherData['user'])) {
            return json(['msg' => '参数错误', 'code' => 1001]);
        }
        $return = [];
        $return['id'] = $voucherData['id'];
        $return['pay_type'] = $voucherData['pay_type'];
        $return['user_phone'] = $voucherData['user']['phone'];
        $return['voucher'] = $voucherData['img'];
        $return['cost'] = $voucherData['money'];
        $return['name'] = $voucherData['consignee'];
        $return['balance'] = $voucherData['activation']['balance'];
        $return['created_at'] = $voucherData['created_at'];
        return json(['data' => $return, 'msg' => '查询成功', 'code' => 200]);
    }


    /**
     * @param Request $request
     * 点击确认激活
     */
    public function actSureActivate(Request $request)
    {
        $voucherId = $request->param('voucherId');
        if (empty($voucherId)) {
            return json(['msg' => '参数错误', 'code' => 1001]);
        }
        $voucherData = VoucherModel::get($voucherId);

        if (strpos($voucherData['user']['level'], $voucherData['type']) !== false) {
            return json(['msg' => '该用户已是合伙人,无需重复激活', 'code' => 1002]);
        }
        if ($voucherData['activation']['balance'] < $voucherData['money']) {
            return json(['msg' => '余额不足,暂不能激活', 'code' => 1002]);
        }
        $password = $request->param('password');
        if (empty($password) || md5($password) !== $voucherData['activation']['two_password']) {
            return json(['msg' => '支付密码不正确', 'code' => 1002]);
        }
        Db::startTrans();
        try {
            //扣除自己余额
            $updateUsers[0] = [
                'balance' => ['exp', 'balance -' . $voucherData['money']],
                'id' => $this->userId
            ];
            //修改用户级别
            $level = $voucherData['user']['level'] . $voucherData['type'];
            $updateUsers[1] = [
                'class' => 2,
                'level' => $level,
                'actid' => $this->userId,
                'id' => $voucherData['uid']
            ];
            $user = new UserModel();
            $user->saveAll($updateUsers);
            //增加余额扣除记录
            $list = AccountModel::getAccountData($this->userId, $voucherData['money'], '激活合伙人', 8, 2, $voucherData['type'], $voucherData['uid']);
            AccountModel::create($list);
            //报单中心获取 激活奖,业绩分红
            $rebate = new Rebate();
            $rebate->partnerRebate($this->userId, $voucherData['uid'], $voucherData['id']);
            //修改用户申请状态
            Db::table('sql_voucher')->where('id', $voucherData['id'])->update(['status' => 2]);
            //用户进入公排
            if (db('config')->where('id', 1)->value('switch') == 1) {
                $rebate->superRebate($voucherData['user']['id'], $voucherData['user']['pid'], $voucherData['id']);  //返佣- 直推奖
                $rebate->goQualifying($voucherData['user']['id'], $voucherData['user']['phone'], $voucherData['id']);
            }
            Db::commit();
            return json(['data' => $voucherData['money'],'msg' => '激活成功', 'code' => 200]);
        } catch (Exception $e) {
            Db::rollback();
            return json(['msg' => $e->getMessage(), 'code' => 1003]);
        }
    }


    /**
     * 拒绝用户激活
     */
    public function actRefuse(Request $request)
    {
        $voucherId = $request->param('voucherId');
        if (empty($voucherId)) {
            return json(['msg' => '参数错误', 'code' => 1002]);
        }
        $res = db('voucher')->where('id', $voucherId)->update(['status' => 3]);
        if ($res) {
            return json(['msg' => '已拒绝用户', 'code' => 200]);
        }
        return json(['msg' => '操作失败', 'code' => 1001]);
    }


    /**
     * @return \think\response\Json
     * 展示收款码
     */
    public function showReceivables()
    {
        $qcode = db('qcode')->where('uid', $this->userId)->find();
        return json(['data' => $qcode, 'msg' => '查询成功', 'code' => 200]);
    }


    //上传收款码
    public function receivables(Request $request)
    {       //需传入收款码qcode  支付宝收款码需传入 type=alipay
        $user = UserModel::get($this->userId);
        if ($user['class'] < 3) {
            return json(['msg' => '级别不够', 'code' => 1002]);
        }
        $input = $request->post();
        $type = 'wqcode';
        if (isset($input['type']) && $input['type'] == 'alipay') {
            $type = 'aqcode';
        }
        $file = $request->file('qcode');
        $data = [];
        if (isset($file)) {
            $imgurl = File::upload($file);
            $data[$type] = $imgurl->getData()['data'];
        }
        $data['uid'] = $this->userId;
        $data['created_at'] = date('YmdHis');

        if ($codeId = db('qcode')->where('uid', $this->userId)->value('id')) {
            $res = db('qcode')->where('id', $codeId)->update([$type => $data[$type]]);
        } else {
            $res = db('qcode')->insert($data);
        }
        if ($res) {
            return json(['msg' => '保存成功', 'code' => 200]);
        }
        return json(['msg' => '保存失败', 'code' => 1001]);
    }


    /**
     *注册下级成为合伙人
     * 页面
     */
    public function registPartner()
    {
        $user = UserModel::get($this->userId);
        $balance = $user['balance'];
        $packageArr = [
            '1' => 'A',
            '2' => 'B',
            '3' => 'C'
        ];
        $packageId = [];
        foreach ($packageArr as $key => $val) {
            if (strstr($user['level'], $val)) {
                $packageId[] = $key;
            }
        }
        $package = Db::table('sql_goods')
            ->field('id,price')
            ->whereIn('id', $packageId)->select();
        return json(['data' => ['package' => $package, 'balance' => $balance], 'msg' => '查询成功', 'code' => 200]);
    }

    /**
     * 点击注册
     *
     * @param  int $id
     * @return \think\Response
     */
    public function actRegister(Request $request)
    {
        $input = $request->post();
        $validate = Loader::validate('Users');             //用户注册验证
        if (!$validate->check($input)) {
            return json(['msg' => $validate->getError(), 'code' => 1001]);
        }
        $address = Loader::validate('Address');            //收货地址验证
        if (!$address->scene('register_address')->check($input)) {
            return json(['msg' => $address->getError(), 'code' => 1001]);
        }
        if (empty($input['packageId'])) {
            return json(['msg' => '参数错误', 'code' => 1001]);
        }
        $package = Db::table('sql_goods')->where('id', $input['packageId'])->find();
        $code = $input['code'];
        $time = time() - 600;
        $codeData = db('code')->where(['phone' => $input['phone'], 'type' => 1, 'status' => 1])->order('id', 'desc')->find();
        //TODO:获取验证码
        if ($input['phone'] != $codeData['phone'] && $code != $codeData['code']) {
            return json(['msg' => '验证码不正确', 'code' => 1002]);
        }
        if (strtotime($codeData['created_at']) < $time) {
            return json(['msg' => '验证码已失效,请重新获取', 'code' => 1010]);
        }
        $user = UserModel::get($this->userId);
        if (!isset($input['password']) || md5($input['password']) !== $user['two_password']) {
            return json(['msg' => '支付密码不正确', 'code' => 1003]);
        }
        if (!strstr($user['level'], $package['unit'])) {
            return json(['msg' => '购买错误', 'code' => 1003]);
        }
        if ($user['balance'] < $package['price']) {
            return json(['msg' => '余额不足', 'code' => 1002]);
        }
        Db::startTrans();
        try {
            //扣除用户余额
            db('users')->where('id', $this->userId)->setDec('balance', $package['price']);

            //增加用户
            $userData = [];
            $falg = ['password' => foo(6), 'two_password' => rand(100000, 999999)];      //获取登陆密码支付密码
            $userData['pid'] = $this->userId;
            $userData['actid'] = $this->userId;
            $userData['phone'] = $input['phone'];
            $userData['unique'] = md5($input['phone']);
            $userData['headimgurl'] = config('back_domain') . '/uploads/default.png';
            $userData['nickname'] = '用户' . foo(4);
            $userData['password'] = md5($falg['password']);
            $userData['two_password'] = md5($falg['two_password']);
            $userData['class'] = 2;
            $userData['level'] = $package['unit'];
            $userData['created_at'] = date('YmdHis');
            $newUser = UserModel::create($userData);

            //增加余额消费记录
            $list = AccountModel::getAccountData($this->userId, $package['price'], '注册新合伙人', 8, 2, $package['unit'], $newUser['id']);
            AccountModel::create($list);

            $list = VoucherModel::getVoucherData($newUser['id'], $this->userId, $package['price'], '', $package['unit'], 3, $package['name'], $package['price'], $package['img'], $input['consignee'], $input['mobile'], $input['province'], $input['city'], $input['area'], $input['detail'], 2);
            $voucher = VoucherModel::create($list);

            //激活合伙人-报单中心返佣(及余额记录)
            $rebate = new Rebate();
            $rebate->partnerRebate($this->userId, $newUser['id'], $voucher['id']);
            //进入公排
            if (db('config')->where('id', 1)->value('switch') == 1) {
                $rebate->goQualifying($newUser['id'], $newUser['phone'], $voucher['id'], $this->userId);
            }
            //激活合伙人-上级报单中心返佣(及余额记录)
            $rebate->superRebate($newUser['id'], $this->userId, $voucher['id']);

            //保存用户关系
            $login = new Login();
            $login->saveUserRelation($newUser['id'], $newUser['pid']);
            //TODO:发送短信,告知用户账号密码
            $msg = new MsgCode();
            $msg->sendMsg($newUser['phone'], 4, $falg);
            //验证码修改状态
            db('code')->where('id', $codeData['id'])->update(['status' => 2]);
            Db::commit();
            return json(['msg' => '注册成功', 'code' => 200]);
        } catch (Exception $e) {
            Db::rollback();
            return json(['msg' => '注册失败', 'code' => 1004]);
        }
    }

    /**
     *
     *申请报单中心
     */
    public function applyCenter(Request $request)
    {
        if (db('apply')->where(['uid' => $this->userId, 'status' => 1])->count() >= 1) {
            return json(['msg' => '您已提交申请,请勿重复提交', 'code' => 1001]);
        }
        if (db('users')->where(['id' => $this->userId])->value('class') < 2) {
            return json(['msg' => '您还不是合伙人,暂无法申请', 'code' => 1001]);
        }
        $input = $request->post();
        $validate = Loader::validate('Apply');
        if (!$validate->check($input)) {
            return json(['msg' => $validate->getError(), 'code' => 1001]);
        }
        $where = [];
        $where['level'] = $input['level'];
        if ($input['level'] <= 5) {
            $where['province'] = $input['province'];
        }
        if ($input['level'] <= 4) {
            if (empty($input['city'])) {
                return json(['msg' => '城市不能为空', 'code' => 1001]);
            }
            $where['city'] = $input['city'];
        }
        if ($input['level'] == 3) {
            if (empty($input['city']) || empty($input['area'])) {
                return json(['msg' => '城市和县/区都不能为空', 'code' => 1001]);
            }
            /*$where['area'] = $input['area'];
            $where['level'] = 3;*/
        }
        $where['status'] = 2;
        $res1 = db('apply')->where($where)->count();
        if ($res1 >= 1) {
            return json(['msg' => '该地区已有报单中心,请选择其他地区', 'code' => 1002]);
        }

        $input['uid'] = $this->userId;
        $input['created_at'] = date('YmdHis');
        $res = ApplyModel::create($input);
        if ($res) {
            return json(['msg' => '申请报单中心成功', 'code' => 200]);
        }
        return json(['msg' => '提交申请失败', 'code' => 1002]);
    }


}
