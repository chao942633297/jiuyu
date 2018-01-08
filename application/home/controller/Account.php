<?php
namespace app\home\controller;

use app\backsystem\controller\File;
use app\backsystem\model\AccountModel;
use app\backsystem\model\UserModel;
use app\backsystem\model\WithdrawModel;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class Account extends Base{

    protected $userId;
    protected $base;
    protected $charge;

    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->userId = session('home_user_id');
        //参数设置,从配置中读取
        $config = file_get_contents('config');
        $conf = unserialize($config);
        $this->base= $conf['times'];
        $this->charge= $conf['charge'];
    }


    //我的账户
    public function index(){
        $userData = Db::table('sql_users')
            ->field('balance,frozen_price')
            ->where('id',$this->userId)->find();
        $userData['totalMoney'] = bcadd($userData['balance'],$userData['frozen_price'],2);
        return json(['data'=>$userData,'msg'=>'查询成功','code'=>200]);
    }


    //收支详情
    public function fundDetail(){           //收入记录传入inc=1 支出记录传入inc=2
        $inc = input('inc');
        $list = 10;
        $page = input('page')?:1;
        $page = ($page - 1) * $list;
        $fundData = AccountModel::all(function($query)use($inc,$page,$list){
            $query->order('id','desc');
            $query->limit($page,$list);
            $query->field('balance,remark,package_type,type,withdraw_id,status,create_at,from_uid');
            $query->where(['inc'=>$inc,'uid'=>$this->userId]);
        });
        $return = [];
        foreach($fundData as $key=>$val){
            $return[$key]['balance'] = $val['balance'];
            $return[$key]['create_at'] = $val['create_at'];
            $return[$key]['type'] = $val['type'];
            if($val['type'] == 4){
                $return[$key]['headimgurl'] = $val['from']['headimgurl'];
                $return[$key]['nickname'] = $val['from']['nickname'];
            }
            if($val['withdraw_id']){
                $return[$key]['status'] = config('Withdraw_status')[$val['withdraw']['status']];
            }
        }
        return json(['data'=>$return,'msg'=>'查询成功','code'=>200]);
    }


    //我的余额(好友转账页面/提现页面)
    public function myBalance(){
        $user = db('users')->where('id',$this->userId)->find();
        $return['base'] = $this->base;         //提现金额必须100的倍数
        $return['charge'] = $this->charge;       //提现手续费为5%
        $return['balance'] = $user['balance'];
        $return['headimgurl'] = $user['headimgurl'];
        $return['nickname'] = $user['nickname'];
        return json(['data'=>$return,'msg'=>'查询成功','code'=>200]);
    }


    //执行提现
    public function actWithdraw(Request $request){            //需传入提现金额money 提现方式type 1=微信2=支付宝3=银行卡 ,支付密码password
        $input = $request->post();
        $rule = [
            ['money','require|number','提现金额不能为空|提现金额必须是数字'],
            ['type','require|number','提现方式不能为空|提现方式错误'],
            ['password','require','支付密码不能为空']
        ];
        $validate = new Validate($rule);
        if(!$validate->check($input)){
            return json(['meg'=>$validate->getError(),'code'=>1001]);
        }
        $type = $input['type'];           //提现方式1支付宝2微信
        $money = $input['money'];
        $user = UserModel::get($this->userId);
        if($type == 1){
            if(empty($user['alipay'])){
                return json(['msg'=>'请先绑定支付宝','code'=>1011]);
            }
        }else{
            if(empty($user['openid'])){
                return json(['msg'=>'请先绑定微信','code'=>1012]);
            }
        }
        if(md5($input['password']) !== $user['two_password']){
            return json(['msg'=>'支付密码错误','code'=>1006]);
        }
        if(empty($money) || $money <= 0){
            return json(['msg'=>'提现金额错误','code'=>1002]);
        }else if($money % $this->base != 0){              //提现金额必须100的倍数
            return json(['msg'=>'提现金额需为'.$this->base.'的倍数','code'=>1002]);
        }else if($money > $user['balance'] ){
            return json(['msg'=>'余额不足','code'=>1002]);
        }
        Db::startTrans();
        try{
            //减少用户余额
            UserModel::get($this->userId)->setDec('balance',$money);
            //增加提现记录
            $data['uid'] = $this->userId;
            $data['money'] = $money;
            $data['charge'] = $this->charge;        //提现手续费为5%
            $data['realmoney'] = $money * (100 - $this->charge) * 0.01;
            $data['status'] = 1;
            $data['created_at'] = date('YmdHis');
            $data['type'] = $type;
            $withdraw = new WithdrawModel();
            $res = $withdraw->insertGetId($data);
            //增加余额消费记录
            $record = AccountModel::getAccountData($this->userId,$money,'余额提现',5,2,'','',$res);
            AccountModel::create($record);
            //判断用户余额是否是负值
            if(db('users')->where('id',$this->userId)->value('balance') < 0){
                Db::rollback();
                return json(['msg'=>'操作错误','code'=>1001]);
            }else{
                Db::commit();
                return json(['msg'=>'保存成功','code'=>200]);
            }
        }catch(Exception $e){
            Db::rollback();
            return json(['msg'=>$e->getMessage(),'code'=>10011]);
        }
    }




    //提现记录
    public function withdrawRecord(){
        $page = input('page')?:1;
        $list = 10;
        $limit = ($page - 1) * $list;
        $withdrawData = Db::table('sql_withdraw')
            ->field('money,status,created_at')
            ->where('uid',$this->userId)
            ->limit($limit,$list)
            ->order('id','desc')->select();
        foreach($withdrawData as $key=>$val){
            $withdrawData[$key]->status = config('Withdraw_status')[$val['status']];
        }
        return json(['data'=>$withdrawData,'msg'=>'查询成功','code'=>200]);
    }


    //执行好友转账
    public function actTransfer(){       //需传入转账好友手机号friend 传入转账金额 money
        $friend = input('friend');
        $money = input('money');
        $password = input('password');
        $friendData = UserModel::get(['phone'=>$friend]);
        if( empty($friend) || empty($friendData)){
            return json(['msg'=>'好友账号不存在','code'=>1001]);
        }
        if($friendData['id'] == $this->userId){
            return json(['msg'=>'不能给自己转账','code'=>1001]);
        }
        $user = UserModel::get($this->userId);
        if(!isset($password) || md5($password) != $user['two_password']){
            return json(['msg'=>'支付密码错误','code'=>1006]);
        }
        if(empty($money) || $money <= 0){
            return json(['msg'=>'转账金额错误','code'=>1003]);
        }else if(floor($money) != $money){
            return json(['msg'=>'转账金额必须为整数','code'=>1005]);
        }else if($money > $user['balance']){
            return json(['msg'=>'余额不足','code'=>1002]);
        }
        Db::startTrans();
        try{
            /*=====================用户余额互转=====================*/
            //扣除用户余额
            UserModel::get($this->userId)->setDec('balance', $money);
            //增加用户转账记录
            $data[0] = AccountModel::getAccountData($this->userId, $money, '好友转账', 4, 2, $friendData['id']);
            //增加好友余额
            $friendData->balance += $money;
            $friendData->total_price += $money;
            $friendData->save();
            //UserModel::get($friendData['id'])->setInc('balance',$money);
            //增加好友余额增加记录
            $data[1] = AccountModel::getAccountData($friendData['id'], $money, '好友转账', 4, 1, $this->userId);
            $res = AccountModel::insertAll($data);
            if ($res) {
                if (db('users')->where('id', $this->userId)->value('balance') < 0) {
                    Db::rollback();
                    return json(['msg' => '操作错误', 'code' => 1001]);
                } else {
                    Db::commit();
                    return json(['msg' => '转账成功', 'code' => 200]);
                }
            }
        }catch(Exception $e){
            Db::rollback();
            return json(['msg'=>'转账失败'.$e->getMessage(),'code'=>1004]);
        }
    }


    //转账记录
    public function transferRecord(Request $request){
        $page = $request->param('page',1);
        $list = 10;
        $limit = ($page - 1) * $list;
        $inc = $request->param('inc');          //1收入 2 支出
        if(!in_array($inc,[1,2])){
            return json(['msg'=>'参数错误','code'=>1001]);
        }
        $transferData = AccountModel::all(function($query)use($limit,$list,$inc){
            $query->order('id','desc');
            $query->limit($limit,$list);
            $query->field('uid,balance,create_at,inc,from_uid');
            $query->where('type',4);
            $query->where(['uid'=>$this->userId,'inc'=>$inc]);
        });
        $return = [];
        foreach($transferData as $key=>$val){
            $return[$key]['from_name'] = $val['from']['nickname'];
            $return[$key]['from_headimgurl'] = $val['from']['headimgurl'];
            $return[$key]['balance'] = $val['balance'];
            $return[$key]['create_at'] = $val['create_at'];
            $return[$key]['inc'] = $val['inc'];
        }
        return json(['data'=>$return,'msg'=>'查询成功','code'=>200]);
    }



    //直推奖/感恩奖/激活奖
    public function accountBonus(){           //直推奖传入type = 1 感恩奖传入type = 2 激活奖传入 type= 8
        $type = input('type');
        $page = input('page')?:1;
        $list = 10;
        $limit = ($page - 1) * $list;
        $where['uid'] = $this->userId;
        $where['type'] = $type;
        $where['inc'] = 1;
        $accountData = AccountModel::all(function($query)use($where,$limit,$list){
            $query->order('id','desc');
            $query->field('balance,type,status,create_at,from_uid');
            $query->limit($limit,$list);
            $query->where($where);
        });
        $return = [];
//        $frozen = 0;
        if($type == 2){
//            $where['status'] = 1;
//            $frozen = db('account')->where($where)->sum('balance');
            $where['status'] = 2;
        }
        $totalPrice = db('account')->where($where)->sum('balance');
        foreach($accountData as $key=>$val){
            $return[$key]['balance'] = $val['balance'];
            $return[$key]['status'] = $val['status'];
            $return[$key]['create_at'] = $val['create_at'];
            $return[$key]['userName'] = $val['from']['nickname'];
            $return[$key]['userPhone'] = $val['from']['phone'];
            $return[$key]['headimgurl'] = $val['from']['headimgurl'];
        }
        return json(['data'=>['return'=>$return,'totalPrice'=>$totalPrice/*,'frozen'=>$frozen*/],'msg'=>'查询成功','code'=>200]);
    }


    //业绩分红
    public function achievement(){           //业绩分红type = 9
        $page = input('page')?:1;
        $list = 12;
        $limit = ($page - 1) * $list;
        $accountData = AccountModel::all(function($query)use($limit,$list){
            $query->field('sum(balance) as monthMoney,create_at');
            $query->limit($limit,$list);
            $query->where(['uid' => $this->userId, 'type' =>9]);
            $query->group('month(create_at)');
        });
        $return = [];
        $totalPrice = 0;
        foreach($accountData as $key=>$val){
            $return[$key]['monthMoney'] = $val['monthMoney'];
            $return[$key]['create_at'] = substr($val['create_at'],0,7);
            $totalPrice += $val['monthMoney'];
        }
        return json(['data'=>['return'=>$return,'totalPrice'=>$totalPrice],'msg'=>'查询成功','code'=>200]);
    }




}