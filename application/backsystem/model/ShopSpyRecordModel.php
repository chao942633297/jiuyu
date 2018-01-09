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
                $accountData['balance'] = $param['amount']; //账户扣除后的余额
                $accountData['remark'] = '窥探消费';
                // $accountData['money'] = $param['amount'];
                $accountData['inc'] = 2;     // 1增加 2 减少
                $accountData['type'] = 13;  // 扣币类型 13：窥探消费
                $accountData['create_at'] = date('Y-m-d H:i:s');
                
                AccountModel::create($accountData);

            }

            // $result = $this->validate('ShopSpyRecord')->insert($param);
            $result = Db::table('sql_shop_spy_record')->insertGetId($param);
            if($result < 0){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                Db::table('sql_shop_goods')->where('id',$param['goodsid'])->setDec('sur_price',$param['amount']);   
                Db::table('sql_shop_goods')->where('id',$param['goodsid'])->setInc('spy_price',$param['amount']);   
                Db::table('sql_shop_goods')->where('id',$param['goodsid'])->setInc('spy_amount',$param['amount']);   
                $re = Db::table('sql_shop_goods')->where('id',$param['goodsid'])->field('*')->find();   
                // if ($re['sur_price'] <= 0) {
                //窥探价格累计超越原价 产生中奖记录
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
                    $successData['payment'] = $param['payment'];
                    $successData['created_at'] = $param['created_at'];
                    db('shop_spy_success')->insert($successData);
                    Db::table('sql_shop_goods')->where('id',$param['goodsid'])->setInc('times');
                    Db::table('sql_shop_goods')->where('id',$param['goodsid'])->setField('last_wintime',date("Y-m-d H:i:s"));


                    // 获奖会员最后一下次窥探金额大于商品所剩金额 处理方法


                    Db::commit();
                    return ['code' => 1, 'data' => $re['spy_price'], 'msg' => '恭喜您在窥探游戏中获得奖品 '.$re["name"].' ，我们会尽快与您取得联系并发放奖品'];
                    exit;
                }
                
                if (($re['spy_price'])/($re['price']) > 0.6) {
                    $re['spy_price'] = " 价格低于30%不予显示";
                }

                // if (($re['sur_price'])/($re['price']) < 0.3) {
                //     $re['spy_price'] = " 价格低于30%不予显示";
                // }
                Db::commit();
                return ['code' => 1, 'data' => $re['spy_price'], 'msg' => '窥探成功'];
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