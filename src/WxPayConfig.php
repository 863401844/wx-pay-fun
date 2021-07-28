<?php
/**
 * Created by PhpStorm.
 * User: 86340
 * Date: 2021/7/27
 * Time: 16:58
 */

namespace WxPayFun;


class WxPayConfig
{
    protected static $wx_mch_id;
    protected static $serial_no;
    protected static $wx_APIv3;
    protected static $wx_appid;
    protected static $apiclient_key;
    protected static $cert;

   static public function initConfig($config){
       self::$wx_mch_id     = isset($config['wx_mch_id']) ? $config['wx_mch_id'] : '';      //商户号
       self::$serial_no     = isset($config['serial_no']) ? $config['serial_no'] : '';      //api证书号(设置v3证书获得的)
       self::$wx_APIv3      = isset($config['wx_APIv3']) ? $config['wx_APIv3'] : '';       //APIv3密钥
       self::$wx_appid      = isset($config['wx_appid']) ? $config['wx_appid'] : '';       //微信appid

       self::$apiclient_key = isset($config['apiclient_key']) ? $config['apiclient_key'] : '';  //API安全->申请证书 完成步骤获得的 apiclient_key.pem文件路径
       self::$cert          = isset($config['cert']) ? $config['cert'] : '';           //获取平台证书接口中获取的 平台证书文件路径
    }
}