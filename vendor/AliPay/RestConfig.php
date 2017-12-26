<?php
namespace Vendor\AliPay;
class RestConfig
{
    public static function config()
    {
        return $config = array(
            //应用ID,您的APPID。
            'app_id' => "2017052607354892",

            //商户私钥，您的原始格式RSA私钥
            'merchant_private_key' => "MIICWwIBAAKBgQDPiUFiAPrSQ/wLplqPq285wl5xmG96naszmqsqU0qsXRn1fY5JyCoKfcqlWcGslmrnhiw79n8tTw3ESIyI+P9HSr3ismLLOztdkILr3zSg4em6fH8ZISNaLjyf6atD1vI2hTZNmAbTApu+4YxL6u+nBhwbwtBRWPTwc1q6vVmB+QIDAQABAoGAQWrwcyX/6huH7Vwom7TcQIamIoR8T1g3yPJuFc9fcGmAb0N+gH9Z0SjJocljJTXcyNIgS15txChxHHgJ5HsobA0ZXPc4KvM+MyQ2NDvMGJPu2/EP78Ds3tQ1OMvXwXEVkMemHnCKiTFeLpHwi57bUPWXSrLsRvrZybHCwgHbl4kCQQDu39N+k/FqZ5WFSEtz/tzIwvxdjlIELRZ4qdv8Z3lMYW8HkLQ5/L/HbAl9ZFBM0qh9Dun+wRZL2qaiGgqN6CYrAkEA3mpD1m4wWJU+wcaODas9hIAXt9aSA4zPIk1635oGjyNhvCrQraj/R+JB6xgs/vW2hl8nR4E9sGxxR7FP8GuqawJAEkDoWHPVrtvbgSPVIDgJhw3fWwbVHZyUawQP22nMyxlm8p0MKKI3xXVsBDj2KeivF19cYis/GOzMbvaud8mVoQJAG8mzCAtkRuz+lj80aEjIutE2JWXNgFwLVQHRJDaeMyv8fgHraIcAvf5qtfCjTodscoVY5voitvQVgxuIHUWWWQJADMYqUedtVlxbvyJ5btyhciHhG5ffVyD7sy71Xt9Ucg/Ve6bCjy82dEyDdkqGjjqTIcjk778rAyzVmVyDqZWqXw==",

            //异步通知地址
            'notify_url' => "http://ht.xtwhjy.com/home/Not/restNotify",

            //同步跳转
            'return_url' => 'http://xtwhjy.com/xt/jyt_index.html',

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