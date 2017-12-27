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

class AdminValidate extends Validate
{
    protected $rule = [
        ['classname', 'require', '分类名不能为空'],
    ];

}