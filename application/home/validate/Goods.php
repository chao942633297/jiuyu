<?php
namespace app\home\validate;



use think\Validate;


class Goods extends Validate{

    protected $rule = [
//        ['buyer_name','require|max:25','用户名不能为空|名称最多不能超过25个字符'],
//        ['buyer_phone','require|/^1[34578]\d{9}$/','手机号不能为空|请输入正确的手机号'],
        ['addrId','require','收货地址不能为空'],
        ['carId','require','请选择购买车型'],
        ['password','require','请输入支付密码'],
    ];

}




