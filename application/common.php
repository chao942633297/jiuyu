<?php

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装） 
 * @return mixed
 */
function get_client_ip($type = 0,$adv=false) { 
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if($adv){
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);        
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}


// 判断是否是微信内部浏览器
function is_weixin(){
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
            return true;
        }
            return false;
}


/**
 * 随机生成订单号
 */
function orderNum(){
    do{
        $num = date('Y').date('m').time().rand(1,100);
    }while(db('order')->where(['order_sn'=>$num])->find());
    return $num;
}


/**
 * 随机生成订单号
 */
function withdrawNum(){
    do{
        $num = date('Y').date('m').time().rand(1,100);
    }while(db('withdraw')->where(['withdraw_sn'=>$num])->find());
    return $num;
}


function getAgentId($province,$city,$area){
    $prentId = [1];
    if($areaId = \think\Db::table('sql_apply')->where(['province'=>$province,'city'=>$city,'area'=>$area,'status'=>2,'level'=>3])->column('uid')){
        $prentId = $areaId;
    }else if($cityId = \think\Db::table('sql_apply')->where(['province'=>$province,'city'=>$city,'status'=>2,'level'=>4])->column('uid')){
        $prentId = $cityId;
    }else if($provinceId = \think\Db::table('sql_apply')->where(['province'=>$province,'status'=>2,'level'=>5])->column('uid')){
        $prentId = $provinceId;
    }
    return $prentId;
}





//获取所有下级
function getSubtree($arr,$parent=0){
    $task = array($parent);//创建任务表
    $subs = array();//存子孙栏目的数组
    while(!empty($task))//如果任务表不为空 就表示要做任务
    {
        $flag = false;//默认没找到子树
        foreach($arr as $k=>$v){
            if($v->pid == $parent){
                $subs [] = $v->id;
                array_push($task,$v->id);//借助栈 把新的地区的id压入栈
                $parent = $v->id;
                unset($arr[$k]);//把找到的单元unset掉
                $flag = true;
            }
        }
        if(!$flag){//表示没找到子树
            array_pop($task);
            $parent = end($task);
        }
    }
    return $subs;
}

function foo($size=16) {
    $dict = '0123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ';
    $len = strlen($dict);
    $res = '';
    for($i=0; $i<$size; $i++) $res .= $dict{rand(0, $len - 1)};
    return $res;
}



/**
 * 对象转换成数组
 * @param $obj
 */
function objToArray($obj)
{
    return json_decode(json_encode($obj), true);
}


//对二维数组某个字段排序
function multi_array_sort($multi_array,$sort_key,$sort=SORT_ASC){
    if(is_array($multi_array)){
        foreach ($multi_array as $row_array){
            if(is_array($row_array)){
                $key_array[] = $row_array[$sort_key];
            }else{
                return false;
            }
        }
    }else{
        return false;
    }
    array_multisort($key_array,$sort,$multi_array);
    return $multi_array;
}




/**
 * 模拟提交参数，支持https提交 可用于各类api请求
 * @param string $url ： 提交的地址
 * @param array $data :POST数组
 * @param string $method : POST/GET，默认GET方式
 * @return mixed
 */
function http($url, $data='', $method='GET'){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        exit(curl_error($ch));
    }
    curl_close($ch);
    // 返回结果集
    return $result;
}

//用户名、邮箱、手机账号中间字符串以*隐藏
function hideStar($str) {
    if(empty($str)){
        return '';
    }
    if (strpos($str, '@')) {
        $email_array = explode("@", $str);
        $prevfix = (strlen($email_array[0]) < 4) ? "" : substr($str, 0, 3); //邮箱前缀
        $count = 0;
        $str = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $str, -1, $count);
        $rs = $prevfix . $str;
    } else {
        $pattern = '/(1[3458]{1}[0-9])[0-9]{4}([0-9]{4})/i';
        if (preg_match($pattern, $str)) {
            $rs = preg_replace($pattern, '$1****$2', $str); // substr_replace($name,'****',3,4);
        } else {
            $rs = substr($str, 0, 3) . "***" . substr($str, -1);
        }
    }
    return $rs;
}


/*发送短信验证码
auth:mpc
$mobile:手机号
$code :验证码
*/
function NewSms($Mobile){
      $str = "1234567890123456789012345678901234567890";
      $str = str_shuffle($str);
      $code= substr($str,3,6);
    $data = "username=%s&password=%s&mobile=%s&content=%s";
    $url="http://120.55.248.18/smsSend.do?";
    $name = "SYLJ";
    $pwd  = md5("iK8eH5xX");
    $pass = md5($name.$pwd);
    $to   =  $Mobile;
    $content = "【优恋精选】您的注册验证码是：".$code."，请在10分钟内填写，切勿将验证码泄露于他人！";
    $content = urlencode($content);
    $rdata = sprintf($data, $name, $pass, $to, $content);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$rdata);
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $result = curl_exec($ch);
    curl_close($ch);
    return ['code' => $code, 'data' => $result, 'msg' => ''];
}


/**
 * 获取本月的开始和结束时间戳
 * @return 本月的开始和结束时间戳  ----array
 */
function getMonth(){
    $return[0] = strtotime(date('Y-m',time()).'-1 00:00:00');
    $return[1] = strtotime(date('Y-m').'-'.date('t').' 23:59:59');
    return $return;
}

/**
 * 获取今天的开始和结束时间戳
 * @return 本月的开始和结束时间戳  ----array
 */
function getDay(){
    $return[0] = strtotime(date('Y-m-d').' 00:00:00');
    $return[1] = strtotime(date('Y-m-d').' 23:59:59');
    return $return;
}




