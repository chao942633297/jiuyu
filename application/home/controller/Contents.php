<?php

namespace app\home\controller;

use app\backsystem\model\ArticleModel;
use think\Controller;
use think\Db;
use think\Request;

class Contents extends Controller
{
    /**
     *项目简介
     */
    public function index()
    {
        $brief = ArticleModel::get(1)['content'];
        return json(['data'=>$brief,'msg'=>'查询成功','code'=>200]);
    }

    /**
     * 新手必看
     */
    public function newHand()
    {
        $news = ArticleModel::all(function($query){
            $query->field('title,id');
            $query->where('type','新手必看');
        });
        return json(['data'=>$news,'msg'=>'查询成功','code'=>200]);
    }

    /**
     * 新手必看详情
     */
    public function newHandDetail(Request $request)
    {
        $newId = $request->param('newId');
        $new = ArticleModel::get($newId);
        return json(['data'=>$new,'msg'=>'查询成功','code'=>200]);
    }

    /**
     * 系统公告
     */
    public function notice()
    {
        $news = ArticleModel::all(function($query){
            $query->field('id,title,description,create_at');
            $query->where('type','系统公告');
        });
        return json(['data'=>$news,'msg'=>'查询成功','code'=>200]);
    }

    /**
     * 系统公告详情
     */
    public function noticeDetail(Request $request)
    {
        $noticeId = $request->param('noticeId');
        $data = Db::table('sql_article')->where('id',$noticeId)->find();
        $return = [];
        $return['title'] = $data['title'];
        $return['created_at'] = date('Y-m-d H:i:s',$data['create_at']);
        $return['content'] = $data['content'];
        if(session('home_user_id')){
            $user = Db::table('sql_users')->where('id',session('home_user_id'))->find();
            $allid = json_decode($user['notice_id'],true);
            array_push($allid,$noticeId);
            $user['notice_id'] = json_encode($allid);
            Db::table('sql_users')->update($user);
        }
        return json(['data'=>$return,'msg'=>'查询成功','code'=>200]);
    }


    /**
     * 客服中心
     */
    public function service(){
        $service = ArticleModel::get(15)['content'];
        return json(['data'=>$service,'msg'=>'查询成功','code'=>200]);
    }

    /**
     * 招商政策
     */
    public function invest()
    {
        $invest = ArticleModel::get(2)['content'];
        return json(['data'=>$invest,'msg'=>'查询成功','code'=>200]);
    }


}
