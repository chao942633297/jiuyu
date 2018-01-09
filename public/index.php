<?php
/*header("location:aliyun.html");
exit;*/
// [ 应用入口文件 ]
//if($_SERVER['REQUEST_URI']=='/'){
//	header('Location:/home/');
//	die();
//}

$allow_origin = array(
    'http://jiuyu.app',
    'http://admin.jiuyushangmao.com',
    'http://localhost:8080'
);
$origin = isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN'] : '';

if( !empty($origin) && !in_array($origin, $allow_origin)){
    echo '域名错误!';die;
}
header('Access-Control-Allow-Origin:'.$origin);
//header('Access-Control-Allow-Origin:*');
header("Access-Control-Allow-Credentials:true");

//header('Access-Control-Allow-Origin:http://cnqianming.com');
//header('Access-Control-Allow-Credentials:true');
# 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
# 定义应用缓存目录
define('RUNTIME_PATH', __DIR__ . '/../runtime/');
# 定义项目根目录
define('ROOT_PATH',__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);
# 开启调试模式
define('APP_DEBUG', false);
# 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
