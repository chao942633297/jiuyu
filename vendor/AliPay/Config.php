<?php
namespace Vendor\AliPay;
class Config
{
    public static function config()
    {
        return $config = array(
            //应用ID,您的APPID。
            'app_id' => "2016092301960685",

            //商户私钥，您的原始格式RSA私钥
            'merchant_private_key' =>'MIICXQIBAAKBgQC+LDAnvXiI2fc0eR8CgKvu5phlKsh+4ADMvn2+9IKpiXS6ybpe77tvsnW5dXWCQfqnu8KNcWffQ/OBnvoimV6wDoErbPYvvhlKK9oVt0PGqvP0yTtCccyam0nd6BBBVLII2zqQUcrdIUg34onjSh2fRlZ2+IGuE9D/cGg2wpRt0QIDAQABAoGAQae70FyHmqe1wvX2EMUfltEh1/uXUMZBQG6btytvWNzN4hb3MwONMJjqL+cvdJMW2IXoOzDl7ZzmGuAp93v/xiwe1ijKdz2YNnUNnq9hS9Iff2k3Fcyr8MyOj/nr1FG8f95SiBk9ZVm6MYL0IiqRKSWRo1wNi7bkUhsRHf1ufuECQQDmH5t6tC9tUGhuUzqzwu8/R71As6Otry+QSNZsmM4OXRi5UMyrqkyZpi+IDYQublVIhj0CHE4wyDIj5yiGztRlAkEA046LlUqfWa8bYmCAqwZWiIg+Ctf2tFh2dwTHkq+cwh/36FF3qfUK6p3a20VB9EuoUqPXPIMztd3otZQep+MO/QJAEl1HXR1wA3s9OkCuGJZf3V7uPXGYiE3Ir+0AH556Iy9Ov8sw6iom/sQCWwspF0N6ztTXnYmAu+feCHt0An4S+QJBALXu2EtDKVbPKBWqN/zcLn6r6+8XWqotiXNBQP/81ip7o46+wNIAUasCpbv8C4QzrEWlcOwxSL8WX5IIF3T552ECQQDYrekZ8G79KtUWFZsYa9pDSjc8zVRdMDijBacxyTv8Rt7yFkEUFTN4ZQUdmePPALGn0D65CwBds1jnj3sc7IIh',

            //异步通知地址
            'notify_url' => "http://admin.oto178.com/home/Notify/aliPayNotify",

            //同步跳转
            'return_url' => 'http://www.oto178.com/pay_success.html',

            //编码格式
            'charset' => "UTF-8",

            //签名方式
            'sign_type' => "RSA",

            //支付宝网关
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
            'alipay_public_key'=>"MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB",


        );

    }
}