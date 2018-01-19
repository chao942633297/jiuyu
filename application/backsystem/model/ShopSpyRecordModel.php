<?php
namespace app\backsystem\model;


use app\backsystem\model\ShopGoodsModel;
use app\backsystem\model\AccountModel;

use think\Model;
use think\Db;

class ShopSpyRecordModel extends Model
{
    protected $table = 'sql_shop_spy_record';

    /**
     * 根据搜索条件获取商城窥探记录列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @param $order
     * @param $field
     */
    public function getShopSpyRecordByWhere($where, $offset, $limit,$order='id desc',$field='*')
    {
         return $this->where($where)->field($field)->limit($offset, $limit)->order($order)->select();
    }

    /**
     * 根据搜索条件获取商城窥探记录列表信息 没有分页
     * @param $where
     * @param $order
     * @param $field
     */
    public function getShopSpyRecord($where,$order='id desc',$field='*')
    {
         return $this->where($where)->field($field)->order($order)->select();
    }

    /**
     * 根据搜索条件获取所有的商城窥探记录数量
     * @param $where
     */
    public function getAllShopSpyRecord($where)
    {
        return $this->where($where)->count();
    }




    /**
     * 插入商城窥探记录shop_spy_record 同时插入shop_spy_record
     * @param $param  userid once_price spy_num amount goodsid goodsname goodsimgurl
     * @param 
     */
    public function addShopSpyRecord($param)
    {
        Db::startTrans();
        try{
            $param['created_at'] = date('Y-m-d H:i:s');
            if ($param['payment'] == 3) {
                // 扣除用户余额并产生一条消费记录
                // 扣除用户余额
                UserModel::get($param['userid'])->setDec('balance',$param['amount']);
                // 增加用户余额消费记录
                $user = UserModel::get($param['userid']);
                $accountData = [];
                $accountData['uid'] = $param['userid'];
                $accountData['balance'] = $param['amount']; //变动金额
                $accountData['remark'] = '窥探消费';
                // $accountData['money'] = $param['amount'];
                $accountData['inc'] = 2;     // 1增加 2 减少
                $accountData['type'] = 13;  // 扣币类型 13：窥探消费
                $accountData['create_at'] = date('Y-m-d H:i:s');
                
                AccountModel::create($accountData);

            }

            // $result = $this->validate('ShopSpyRecord')->insert($param);
            $result = Db::name('shop_spy_record')->insert($param);
            if($result <= 0){
                // 添加记录失败
                Db::rollback();
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                Db::name('shop_goods')->where('id',$param['goodsid'])->setDec('sur_price',$param['amount']);   
                Db::name('shop_goods')->where('id',$param['goodsid'])->setInc('spy_price',$param['amount']);   
                Db::name('shop_goods')->where('id',$param['goodsid'])->setInc('spy_amount',$param['amount']);   
                $re = Db::name('shop_goods')->where('id',$param['goodsid'])->field('*')->find();   
                // if ($re['sur_price'] <= 0) {
                //窥探价格累计超越原价 产生中奖记录 窥探直接中奖（非抢购中奖）
                if ($re['spy_price'] >= $re['price']) {
                    // 窥探价格清零 中奖生成订单 并插入spy_success表一条记录   更新商品last_wintime
                    $successData = [];
                    $successData['goodsid'] = $re['id'];
                    $successData['goodsname'] = $re['name'];
                    $successData['goodsprice'] = $re['price'];
                    $successData['goodsimgurl'] = $re['imgurl'];
                    $successData['goodscanshu'] = $re['canshu'];
                    $successData['times'] = $re['times'];
                    $successData['once_price'] = $re['once_price'];
                    $successData['userid'] = $param['userid'];
                    $successData['username'] = $param['username'];
                    $user = UserModel::get($param['userid']);
                    $successData['usermobile'] = $user->phone;
                    $successData['payment'] = $param['payment'];
                    $successData['created_at'] = $param['created_at'];
                    Db::name('shop_spy_success')->insert($successData);
                    Db::name('shop_goods')->where('id',$param['goodsid'])->setInc('times');
                    Db::name('shop_goods')->where('id',$param['goodsid'])->setInc('hot');    // 销量
                    Db::name('shop_goods')->where('id',$param['goodsid'])->setInc('realhot'); // 销量
                    // status  0： 正常  1：进入间隔期
                    Db::name('shop_goods')->where('id',$param['goodsid'])->setField(['last_wintime'=>date("Y-m-d H:i:s"),'status'=>'1']);


                    // 获奖会员最后一下次窥探金额大于商品所剩金额 处理方法就是不返了


                    // 将状态为抢购中的订单 status 变为2 抢购失败， 1（默认）：抢购中  2：抢购失败  3：抢购成功
                    if (Db::name('shop_spying_goods')->where(['goodsid'=>$param['goodsid'],'status'=>'1','times'=>$re['times']])->count()) {
                        Db::name('shop_spying_goods')->where(['goodsid'=>$param['goodsid'],'status'=>'1','times'=>$re['times']])->setField('status','2');
                    }


                    Db::commit();
                    return ['code' => 1, 'data' => $re['sur_price'], 'msg' => '恭喜您在窥探游戏中获得奖品 '.$re["name"].' ，我们会尽快与您取得联系并发放奖品'];
                    exit;
                }
                
                if (($re['spy_price'])/($re['price']) > 0.6) {
                    $re['sur_price'] = " 价格低于30%不予显示";
                }

                // if (($re['sur_price'])/($re['price']) < 0.3) {
                //     $re['spy_price'] = " 价格低于30%不予显示";
                // }
                Db::commit();
                return ['code' => 1, 'data' => $re['sur_price'], 'msg' => '窥探成功'];
            }
        }catch( PDOException $e){
            Db::rollback();
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }




    /** 添加抢购记录 
     *
     *
     *
     */
    public function addShopSpying($param)
    {
        Db::startTrans();
        try{
            $param['created_at'] = date('Y-m-d H:i:s');
            if ($param['payment'] == 3) {
                // 扣除用户余额并产生一条消费记录
                // 扣除用户余额
                UserModel::get($param['userid'])->setDec('balance',$param['sur_price']);
                // 增加用户余额消费记录
                // $user = UserModel::get($param['userid']);
                $accountData = [];
                $accountData['uid'] = $param['userid'];
                $accountData['balance'] = $param['sur_price']; //账户扣除后的余额
                $accountData['remark'] = '窥探抢购消费';
                $accountData['inc'] = 2;     // 1增加 2 减少
                $accountData['type'] = 13;  // 扣币类型 13：窥探消费
                $accountData['create_at'] = $param['created_at'];
                
                AccountModel::create($accountData);

            }
            // 更新其他参与本商品本轮次状态为抢购中记录为失败 status=2  并将抢购失败用户金额返还到余额
            if (Db::name('shop_spying_goods')->where(['goodsid'=>$param['goodsid'],'times'=>$param['times'],'status'=>'1'])->count()) {
                $faildata = Db::name('shop_spying_goods')->where(['goodsid'=>$param['goodsid'],'times'=>$param['times'],'status'=>'1'])->find();//理论上应该只有一条记录

                $r = Db::name('shop_spying_goods')->where(['goodsid'=>$param['goodsid'],'times'=>$param['times'],'status'=>'1'])->setField('status','2');
                if ($r) {
                    // 给被顶掉的抢购用户 返钱
                    UserModel::get($param['userid'])->setInc('balance',$faildata['sur_price']);
                    // 增加用户余额消费记录
                    // $user = UserModel::get($param['userid']);
                    $accountData = [];
                    $accountData['uid'] = $param['userid'];
                    $accountData['balance'] = $faildata['sur_price']; // 变动金额
                    $accountData['remark'] = '窥探抢购失败返还';
                    $accountData['inc'] = 1;     // 1增加 2 减少
                    $accountData['type'] = 13;  // 扣币类型 13：窥探消费
                    $accountData['create_at'] = $param['created_at'];
                    
                    AccountModel::create($accountData);
                }else{
                    // 抢购失败订单 重置失败 
                    Db::rollback();
                    return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
                }
            }

            // 添加新的抢购记录
            // $result = $this->validate('ShopSpyRecord')->insert($param);
            $param['status'] = '1';   // 1抢购中  2抢购失败  3抢购成功
            $result = Db::name('shop_spying_goods')->insert($param);

            if($result <= 0){
                // 生成新纪录失败
                Db::rollback();
                return ['code' => -3, 'data' => '', 'msg' => $this->getError()];
            }else{
                Db::commit();
                return ['code' => 1, 'data' => '', 'msg' => '生成抢购订单成功'];
            }
        }catch( PDOException $e){
            Db::rollback();
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据商城窥探记录id获取商城窥探记录信息 
     * @param $id
     * @param $field
     */
    public function getOneShopSpyRecord($id,$field="*")
    {
        $data = $this->where('id', $id)->field($field)->find();

        return $data;
    }


    /**
     * 根据用户id 商品id 获取商城窥探记录信息 
     * @param $uid 
     * @param $goodsid 
     * @param $field
     */
    public function getSpyRecordsByUid($uid,$goodsid,$field="*",$order="id desc")
    {
        $data = $this->where(['userid'=>$uid,'goodsid'=>$goodsid])->field($field)->order($order)->select();

        return $data;
    }


    /**
     * 根据用户id 商品id 获取商城窥探记录信息 
     * @param $uid 
     * @param $goodsid 
     * @param $field
     */
    public function getLastSpyRecords($uid,$goodsid,$field="*")
    {
        $data = $this->where(['userid'=>$uid,'goodsid'=>$goodsid])->field($field)->limit(1)->order("id desc")->select();

        return $data;
    }




}