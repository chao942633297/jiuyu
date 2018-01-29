<?php
namespace app\backsystem\controller;

use app\backsystem\model\AccountModel;
use app\backsystem\model\RowModel;
use app\backsystem\model\UserModel;
use app\home\controller\Rebate;
use think\Db;
use think\Exception;
use think\Log;
use think\Request;

class Rowa extends Base{

    static $thanksA;

    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        //参数设置,从配置读取
        $config = file_get_contents('config');
        $conf = unserialize($config);
        self::$thanksA = $conf['thanksA'];    //管理奖
    }

    /***
		*后台公排列表
		***/
        public function rowlist()
        {
        /*    $info = RowModel::all(function($query){
                $query->field('group_concat(user_phone order by position asc) as combination,group_concat(position order by position asc) as position ');
                $query->group('time');
            });*/
            $info = Db::table('sql_rowA')
                ->field('group_concat(user_phone order by position asc) as combination,group_concat(position order by position asc) as position ')
                ->group('time')->select();
            $lists = [];
            foreach($info as $key=>$val){
                $data = explode(',',$val['combination']);
                $dat = explode(',',$val['position']);
                $lists[$key][1] = ['phone'=>'','num'=>1];
                $lists[$key][2] = ['phone'=>'','num'=>2];
                $lists[$key][3] = ['phone'=>'','num'=>3];
                $lists[$key][4] = ['phone'=>'','num'=>4];
                $lists[$key][5] = ['phone'=>'','num'=>5];
                $lists[$key][6] = ['phone'=>'','num'=>6];
                $lists[$key][7] = ['phone'=>'','num'=>7];
                foreach($dat as $k=>$item){
                    $lists[$key][$item]['phone'] = $data[$k];
                }
            }
            $this->assign('info',$lists);
            return $this->fetch('row/rowlista');
        }


    /**
     * @param Request $request
     * @return \think\response\Json
     * 编辑公排人员
     */
    public function edit_position(Request $request){
        $input = $request->post();
        if($input['position'] != 1 && empty($input['phoneParent']))
            return json(['msg'=>'参数错误','code'=>1001]);
        $user = UserModel::get(['phone'=>$input['phone']]);
        if(!$user)
            return json(['msg'=>'输入的手机号未注册','code'=>1003]);
        if(empty($user['pid']))
            return json(['msg'=>'该用户没有上级,不能进入公排','code'=>1004]);

        $res = [];
        $row = [];
        Db::startTrans();
        try{
            $list = RowModel::getRowData($user['id'],$user['phone'],$input['time'],$input['position']);
            $res = Db::table('sql_rowA')->insert($list);
            //用户进入公排,第一名获得3000感恩奖,奖励
         /*   $row = RowModel::all(function($query)use($input){
                $query->order('id','asc');
                $query->field('id,user_id,user_phone,time,position');
                $query->where('time',$input['time']);
            });*/
            $row = Db::table('sql_rowA')
                ->field('id,user_id,user_phone,time,position')
                ->where('time',$input['time'])
                ->order('id','asc')->select();
            if(in_array($input['position'],[4,5,6,7])){
                UserModel::get($row[0]['user_id'])->setInc('frozen_price',self::$thanksA);
                $list = AccountModel::getAccountData($row[0]['user_id'], self::$thanksA, '感恩奖', 2, 1,'A', 1);
                AccountModel::create($list);
            }
            //若添加的是第七名
            if(isset($res) && $res['position'] == 7){
                $rebate = new Rebate();
                $result = $rebate->reCast($row,1);
                Log::info($result);
            }
            Db::commit();
            return json(['msg'=>'添加成功','code'=>200]);
        }catch(Exception $e){
            Db::rollback();
            return json(['msg'=>$e->getMessage(),'code'=>1002]);
        }


    }


       /***
    	*公排
    	***/
        public function rowIng()
        {
        	#激活进入公排
        	#判断将要进入的小组
        	#查找自己的上三级
            $id= 1;
        	$userPid = db('users')->where(['id'=>$id])->value('pid');
        }
        #查找自己的上三级
        public static function findThree($pid,$num)
        {
        	$num ++;
        	if ($num >3 ) {
        		return false;
        	}
        	$leaderId = db('users')->where(['id'=>$pid])->find();
        	if ($leaderId) {
        		return $leaderId;
        	}
//        		$this->findThree($leaderId['pid'],$num);
        }




}
