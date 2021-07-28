<?php
/**
 * Created by PhpStorm.
 * User: 86340
 * Date: 2021/6/30
 * Time: 10:03
 */

namespace WxPayFun;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use WechatPay\GuzzleMiddleware\Util\PemUtil;
use WechatPay\GuzzleMiddleware\WechatPayMiddleware;

class WxPayFun extends WxPayConfig
{
    use WxCertFun;
    public function relatedConfig(){

        // 商户相关配置
        $merchantId =  self::$wx_mch_id; // 商户号
        $merchantSerialNumber = self::$serial_no; // 商户API证书序列号
        $merchantPrivateKey = PemUtil::loadPrivateKey(self::$apiclient_key); // 商户私钥
        // 微信支付平台配置
        $wechatpayCertificate = PemUtil::loadCertificate(self::$cert); // 微信支付平台证书

        // 构造一个WechatPayMiddleware
        $wechatpayMiddleware = WechatPayMiddleware::builder()
            ->withMerchant($merchantId, $merchantSerialNumber, $merchantPrivateKey) // 传入商户相关配置
            ->withWechatPay([ $wechatpayCertificate ]) // 可传入多个微信支付平台证书，参数类型为array
            ->build();

        // 将WechatPayMiddleware添加到Guzzle的HandlerStack中
        $stack = HandlerStack::create();
        $stack->push($wechatpayMiddleware, 'wechatpay');

        // 创建Guzzle HTTP Client时，将HandlerStack传入
        $client = new Client(['handler' => $stack]);
        return $client;
    }

    function makeRandom($length='32')
    {
        $str = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
            'i', 'j', 'k', 'l','m', 'n', 'o', 'p', 'q', 'r', 's',
            't', 'u', 'v', 'w', 'x', 'y','z', 'A', 'B', 'C', 'D',
            'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L','M', 'N', 'O',
            'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y','Z',
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $keys = array_rand($str, $length);
        $noncestr = '';
        for($i = 0; $i < $length; $i++)
        {
            $noncestr .= $str[$keys[$i]];
        }
        return $noncestr;
    }

    public function wxAppPay($description,$out_trade_no,$notify_url,$amount){
        try{
            $client = $this->relatedConfig();
            $resp = $client->request('POST', 'https://api.mch.weixin.qq.com/v3/pay/transactions/app', [
                'json' => [ // JSON请求体
                    'appid' => self::$wx_appid,
                    'mchid' => self::$wx_mch_id,
                    'description'=>$description,
                    'out_trade_no'=>$out_trade_no,
                    'amount'=>[
                        'total'=>$amount
                    ],
                    'notify_url'=>$notify_url,
                ],
                'headers' => [ 'Accept' => 'application/json']
            ]);
            $body = $resp->getBody();
            $resdata = json_decode($body->getContents(),true);

            $data = [
                'appid'=>self::$wx_appid,
                'timestamp'=>time(),
                'noncestr'=>$this->makeRandom(32),
                'prepayid'=>$resdata['prepay_id']
            ];

            $data['sign']= $this->dqsign($data);
            $data['partnerid'] = self::$wx_mch_id;
            $data['package'] = 'Sign=WXPay';
            return ['error'=>0,'data'=>$data];
        }catch (RequestException $e) {
            // 进行错误处理
            if ($e->hasResponse()) {
                return  ['error'=>1,'msg'=>$e->getResponse()->getBody()];
            }
            return  ['error'=>2,'msg'=>$e->getMessage()];
        }

    }
}