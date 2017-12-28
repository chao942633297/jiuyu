<?php
// +----------------------------------------------------------------------
// | snake
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 
// +----------------------------------------------------------------------
namespace app\backsystem\validate;

use think\Validate;

class ShopGoodsValidate extends Validate
{
    protected $rule = [
        ['name', 'require', '商品名称不能为空'],
        ['price', 'require', '价格不能为空'],
        ['imgurl', 'require', '商品图片不能为空']
        ['is_under', 'require', '请设置商品是否上架']
    ];

}