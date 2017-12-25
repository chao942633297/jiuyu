<?php
namespace app\home\validate;



use think\Validate;


class Apply extends Validate{

    protected $rule = [
        ['name','require|max:25','用户名不能为空|名称最多不能超过25个字符'],
        ['phone','require|/^1[34578]\d{9}$/','手机号不能为空|请输入正确的手机号'],
        ['province','require','省份不能为空'],
        ['level','require','申请级别不能为空'],
    ];

}




