<?php
namespace Service;
/**
* 
*/
Class Check 
{	 
	 #验证姓名
	 public static function CheckName($str){
		  if (!preg_match('/^([\xe4-\xe9][\x80-\xbf]{2}){2,4}$/',$str)) {
    		return '姓名最少两个最多4个汉字';
 	      }
	 }

	 #验证密码
 	  public static function CheckPassword($str){
 	 	if (!preg_match('/^[a-zA-Z0-9_]{6,16}$/',$str)) {
			return '密码必须大于6位少于16位的字母或数字';
	  	}
 	 }
 	 #验证交易密码
 	  public static function CheckDealPass($str){
 	 	if (!preg_match('/^[0-9]{6}$/',$str)) {
			return '交易密码必须为6位数字';
	  	}
 	 }
 	 #生成随机数
 	 public static function RandNumber($user_id)
 	 {
 	 	$str = '';
 	 	do {
 	 		$str =rand(1,100).rand(200,900);
 	 	} while (M('pass_record')->where('user_id='.$user_id.' and pass_number='.$str)->find());
 	 	 return $str;
 	 }
 	 #生成随机数
 	 public static  function RandPass()
 	 {
 	 	$str = md5(rand(0,99)*(substr(time(),0,3)).rand(100,200)*(substr(time(),4,7)));
 	 	return $str;
 	 }
 	 #验证手机号
 	 public static function CheckPhone($phone){
 	    if (!preg_match("/^1[34578]{1}\d{9}$/", I('phone'))) {
			return '手机号不合法';
			}
 	 }

	 #判断两次密码是否一致
 	 public static function CheckRepeat($password,$repassword){
	    if ($password != $repassword) {
			return '两次密码不一致';
	    }
	  }

	   /**
     * 字母分组
     */
     public static function makeGroup($array)
     {
        $group = [];
        foreach ($array as $key => $value) {
                if ($letter = self::getFirstLetter($value['class'])) {
                    $group[$letter][] = $value;
                }
        }
        ksort($group);
        return $group;
     }
     /**
     * 获取手写字母
     */
     public static function  getFirstLetter($str){  

        if(empty($str)){
            return false;
        }  
        $fchar = ord($str{0});  
        if($fchar>=ord('A')&&$fchar<=ord('z')) return strtoupper($str{0});  
        $s1=iconv('UTF-8','gb2312',$str);  
        $s2=iconv('gb2312','UTF-8',$s1);  
        $s=$s2==$str?$s1:$str;  
        $asc=ord($s{0})*256+ord($s{1})-65536;  
        if($asc>=-20319&&$asc<=-20284) return 'A';  
        if($asc>=-20283&&$asc<=-19776) return 'B';  
        if($asc>=-19775&&$asc<=-19219) return 'C';  
        if($asc>=-19218&&$asc<=-18711) return 'D';  
        if($asc>=-18710&&$asc<=-18527) return 'E';  
        if($asc>=-18526&&$asc<=-18240) return 'F';  
        if($asc>=-18239&&$asc<=-17923) return 'G';  
        if($asc>=-17922&&$asc<=-17418) return 'H';  
        if($asc>=-17417&&$asc<=-16475) return 'J';  
        if($asc>=-16474&&$asc<=-16213) return 'K';  
        if($asc>=-16212&&$asc<=-15641) return 'L';  
        if($asc>=-15640&&$asc<=-15166) return 'M';  
        if($asc>=-15165&&$asc<=-14923) return 'N';  
        if($asc>=-14922&&$asc<=-14915) return 'O';  
        if($asc>=-14914&&$asc<=-14631) return 'P';  
        if($asc>=-14630&&$asc<=-14150) return 'Q';  
        if($asc>=-14149&&$asc<=-14091) return 'R';  
        if($asc>=-14090&&$asc<=-13319) return 'S';  
        if($asc>=-13318&&$asc<=-12839) return 'T';  
        if($asc>=-12838&&$asc<=-12557) return 'W';  
        if($asc>=-12556&&$asc<=-11848) return 'X';  
        if($asc>=-11847&&$asc<=-11056) return 'Y';  
        if($asc>=-11055&&$asc<=-10247) return 'Z';  
        return false;;  
       }  
}	