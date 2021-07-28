<?php
/**
 * Created by PhpStorm.
 * User: 86340
 * Date: 2021/6/30
 * Time: 9:41
 */

namespace WxPayFun;

use GuzzleHttp\Client;

trait WxCertFun
{
    /**
     * 更新证书和编号
     * @param string $filename
     * @return mixed
     */
    public function filePutWxCert($filename='wxpay_key'){

        $res = $this->getCertificates();
        $jm = new AesUtil(self::$wx_mch_id);
        $enc = $res['data'][0]['encrypt_certificate'];
        $serial_no = $res['data'][0]['serial_no']; //证书号
        $str = $jm->decryptToString($enc['associated_data'],$enc['nonce'],$enc['ciphertext']); //证书
        file_put_contents($filename.'/cert.pem',$str);
        return $serial_no;
    }

    //获取平台证书
    public function getCertificates()
    {
        $merchant_id                = self::$wx_mch_id;//商户号
        $serial_no                  = self::$serial_no;//API证书序列号
        $sign                       = $this->getSign("https://api.mch.weixin.qq.com/v3/certificates","GET","",$this->get_Privatekey(), $merchant_id, $serial_no);//$http_method要大写
        $header['User-Agent']       = 'https://zh.wikipedia.org/wiki/User_agent';
        $header['Accept']           = 'application/json';
        $header['Authorization']    = 'WECHATPAY2-SHA256-RSA2048 ' . $sign;
        $back                       = $this->httpRequest('GET',"https://api.mch.weixin.qq.com/v3/certificates",$header);
        return $back;
    }

    /**
     * 获取sign
     * @param $url
     * @param $http_method [POST GET 必读大写]
     * @param $body [请求报文主体（必须进行json编码）]
     * @param $mch_private_key [商户私钥]
     * @param $merchant_id [商户号]
     * @param $serial_no [证书编号]
     * @return string
     */
    private function getSign($url, $http_method, $body, $mch_private_key, $merchant_id, $serial_no)
    {
        $timestamp     = time();//时间戳
        $nonce         = $timestamp . rand(10000, 99999);//随机字符串
        $url_parts     = parse_url($url);
        $canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));
        $message       =
            $http_method . "\n" .
            $canonical_url . "\n" .
            $timestamp . "\n" .
            $nonce . "\n" .
            $body . "\n";
        openssl_sign($message, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption');
        $sign  = base64_encode($raw_sign);
        $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $merchant_id, $nonce, $timestamp, $serial_no, $sign);
        return $token;
    }

    public function dqsign($data){
        $mch_private_key = $this->get_Privatekey();
        $message       =
            $data['appid'] . "\n" .
            $data['timestamp'] . "\n" .
            $data['noncestr'] . "\n" .
            $data['prepayid'] . "\n";
        openssl_sign($message, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption');
        $sign  = base64_encode($raw_sign);
        return $sign;
    }

    /**
     * 获取商户私钥
     * @return false|resource
     */
    public function get_Privatekey()
    {
        $mch_private_key  = openssl_get_privatekey(file_get_contents(self::$apiclient_key));//获取私钥
        return $mch_private_key;
    }

    /**
     * 数据请求
     * @param $method
     * @param $url
     * @param array $header
     * @param array $body
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function httpRequest($method,$url,$header = array(),$body = array())
    {
        $client = new Client();
        $response = $client->request($method, $url, [
            'headers' => $header,
            $body
        ]);
        $body = $response->getBody();
        $remainingBytes = json_decode($body->getContents(),true);

        return $remainingBytes;
    }

    /**
     * 从证书中获取私钥
     * @param $cert_path （微信平台证书---从《获取平台证书》接口中获得）
     * @return bool
     */
    private function certPubKey($cert_path){
        $cert_path = file_get_contents($cert_path);
        $pub_key  = openssl_pkey_get_public($cert_path);
        if($pub_key){
            $keyData = openssl_pkey_get_details($pub_key);
            return $keyData['key'];
        }
        return false;
    }

    /**
     * 验证回调主方法
     * @param $http_data header头的信息（微信回调发送的header头）
     * @param $body 应答主体信息 (微信发送的数据)(未解密的数据!!!)
     * @return int 验证成功返回 1  失败返回 0
     */

    public function verifySigns($http_data,$body){
        $wechatpay_timestamp    = $http_data['Wechatpay-Timestamp'];
        $wechatpay_nonce        = $http_data['Wechatpay-Nonce'];
        $wechatpay_signature    = $http_data['Wechatpay-Signature'];
        $signature = base64_decode($wechatpay_signature);
        $pub_key = $this->certPubKey(self::$cert);//平台证书路径
        $body = str_replace('\\','',$body);
        $message       =
            $wechatpay_timestamp . "\n" .
            $wechatpay_nonce . "\n" .
            $body . "\n";
        $res = openssl_verify($message,$signature, $pub_key,OPENSSL_ALGO_SHA256);

        if ($res == 1) {
            return 1;
        }
        return 0;
    }

}