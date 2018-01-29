<?php
namespace app\home\validate;



use think\Validate;


class Shoporder extends Validate{

    protected $rule = [
        ['buyer_name','require|max:25','用户名不能为空|名称最多不能超过25个字符'],
        ['buyer_phone','require|/^1[34578]\d{9}$/','手机号不能为空|请输入正确的手机号'],
        ['province','require','请输入省'],
        ['city','require','请输入市'],
        ['area','require','请输入区域'],
    ];

}




