<?php
namespace Vendor\AliPay;
class Config
{
    public static function config()
    {
        return $config = array(
            //应用ID,您的APPID。
            'app_id' => "2017122201066532",

            //商户私钥，您的原始格式RSA私钥
            'merchant_private_key' =>'MIIEowIBAAKCAQEA5IQ1sNxVQkDgpZMnKRReGAcmq6noAqWlC8xLoQbHNjv4Y5AQwZC/Gd+cfHBtkMBMu5L8HtT4240gullCEJtK16/OQORA0HvOb2UUuYXvXjNszd8qC4wUxwWnsovfvS7+VRsKRUIWSAOzkb3krEGysUuUvkeHAPEnPW4gOMUWCi/2hWKx/djRidByEP5mUcbP1h95lHn8TUMkPYbvGww1srbdjLzfVaAn/lJ+whnvJvTMsiENGmyhPwlkvgl16BWZ1nAzDF0oH6rDANhOczSIZ7cAU30vRaBKv/+EnvJ7CxWy6Qu5vjSq/me1U+nk04gEGXxtqtgkyGYGydJDrYtUXwIDAQABAoIBACh+GPl/AYic8HHGkpDf8BB36+1NTTRy370odVpSViiRFeGmnKGAIazXB+axEWkt+irez9gyyuo0ptD+kgtZSTjRCU5MZAPNfHYWxkZdql3Z5PTSD6Q+LUxa/y1PRdVDJ4QzJ23AWSxno4E20feIKL7r8q6JBC4xjU+AXStSGSaypTxb1DaIgzSfdkGGPsLhU/XCct5UONc4eHy/JDIcFykMgylKor1jXTUq3VK2vANEh4pOif0OgbskYnv31XSjFiUhpLkh8r7MNDPXzejkEscCBHX/NpaLgJKoh0x8gu8ARumXzlltG/Lefumjf2tCTBjrd6jvQlDj2pDF6VDkO0kCgYEA/jLNBy2/t98vTgF6Z7wf3nzVo1sfvx+fE/zQgbsi1pwYmkTgO4ZoZ1O64ANj/EwbNkzjpqA8gALckaZ5CgDAfDES0BEidIbRi1NRdELpLG7GiUERcT2RD8JfS3ce0EXyMgIF6d+XbIa8p4WmC6VKFU3eVr/9GtIGyI7mhLNYuYMCgYEA5iLQNEXdUm1xIZHld8f6KdMCALVOLVGWlC7SI13VSH1q9hBhhaFdCdHOsqkEliih137rbug414yAd/9Umyf58g9FJCNbav0mqLKebE4CqiO7U//M93ZNBJT6wo6fltpRjk7mxmKr2eFFvkoDMPdbo/6SSHbLXCPeHqvG6HgO7vUCgYAKaw6YIrne0Vjg+5KGueEcf0VKQqvUa3lbmlr5VjAhV5hyiiwehG22/mmEUN9CMeRTn2cdJygTnwpnNcl0LX+2F1TMDke5OuVProSCHVPtLEUazv3mBD3zxWWedC1hH9zDS+3uHenY2tTUkNezVnzozp40M/4toToB8klkWu1h/QKBgQDR0I+N+NxNWCY6eu+AgzvagdxjlOjPfIESXJWNVPEtA9tOt6SR1ooid7xBOsNJu4XCGJ7BIMsiCaDexlT2mD3SqIVa6zlfk6l5SFATYhQf1i/l52ORgbO6J7FvS+TH/gc4/Up9OFyBalbRpFzAeeR84Y2wr028lZ7ey7GvJDZJOQKBgH02mDUWzbdnadBIbb8tZW0nWEnXhFWsmWDQVD+Fe9EFLmOGacJMuizCrxbXary9GqGqWNgngjMfL/dZ60cOE3DW8d+2OShDIXcrroKceML+mThyZJFf85Hfr/wIaQNrJk0Gxv65hnFnY8HFXyGigImDygBAUYk7nb0tdLzHRoCR',

            //异步通知地址
            'notify_url' => "http://admin.jiuyushangmao.com/home/Notify/aliPayNotify",

            //同步跳转
            'return_url' => 'http://web.jiuyushangmao.com/success',

            //编码格式
            'charset' => "UTF-8",

            //签名方式
            'sign_type' => "RSA2",

            //支付宝网关
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
            'alipay_public_key'=>"MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhl2pbbSAsUFMHrhlBGynX1mqp40Rwma5ZIStAo9jsYjKNLKX9T3syNlDJL1OClyOzndpWZZDcNbR08weRjTpKqdYf7lpFHJabr0vyjp99aMKQprI/X5seMsTf/551IzKMM5aetSTwIJD0Ai//QU2eqU2U0ywJGrW4ULSyF+7ZtIsbx3IC2ZX6+HreYMIjLW4ChDM7Ac/OBvYfWCvEgD/OT1PRQF1AW6lH5osnJaueXFBqBx2jWNAhYDRUQkZiolnP1iCCpY5m3CbmMYoFrQmS7B2TQpEDAeAgs86Nobj/7UzQPnyCeCoJHfL7PzuYnNVr0Gbfu01Rrp2On67Mc98VwIDAQAB",


        );

    }
}