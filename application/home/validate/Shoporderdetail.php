<?php
namespace app\home\validate;



use think\Validate;


class Shoporderdetail extends Validate{

    protected $rule = [
        ['goodsid','require','商品ID不能为空'],
        ['userid','require','用户ID不能为空'],
        ['goodsnum','require','商品数量不能为空'],
    ];

}




