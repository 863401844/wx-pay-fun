# wechatpay-guzzle-middleware

## 概览

基于[微信支付API v3](https://wechatpay-api.gitbook.io/wechatpay-api-v3/)，再次封装。继承了签名生成丶回调验签丶证书中提取公钥丶报文解密等





## 项目状态

当前版本为`0.0.1`测试版本。



## 环境要求

我们开发和测试使用的环境如下：

+ PHP 7.0+
+ guzzlehttp/guzzle 6.0+
+ wechatpay/wechatpay-guzzle-middleware ^0.2.0



## 安装

可以使用PHP包管理工具composer引入SDK到项目中：

#### Composer

方式一：在项目目录中，通过composer命令行添加：
```shell
composer require syxwxpay/wxpay-common-fun
```




## 开始 直接调用微信支付已APP支付为例


```php

//第一步 先加载配置文件
$data = [
    'wx_mch_id'=>'1',//商户号
    'serial_no'=>'1',//api证书号(设置v3证书获得的)
    'wx_APIv3'=>'1',//APIv3密钥
    'wx_appid'=>'1', //微信appid
    'apiclient_key'=>__DIR__.'/wxpay_key/apiclient_key.pem', //API安全->申请证书 完成步骤获得的 apiclient_key.pem文件路径
    'cert'=>__DIR__.'/wxpay_key/cert.pem' //获取平台证书接口中获取的 平台证书文件路径 获取方法可查看下方的【## 获取平台证书】
];
WxPayFun\WxPayConfig::initConfig($data); //必须初始化
$test = new WxPayFun\WxPayFun();

//接下来，正常使用Guzzle发起API请求  可以参照微信支付API列表进行传参
$client  = $test->relatedConfig();

$resp = $client->request('POST', 'https://api.mch.weixin.qq.com/v3/pay/transactions/app', [
                'json' => [ // JSON请求体
                    'appid' => 1, //你的appid
                    'mchid' => 1,   //你的商户号
                    'description'=>$description, //商品描述
                    'out_trade_no'=>$out_trade_no, //订单号
                    'amount'=>[
                        'total'=>$amount //金额分 int类型 传字符串会报错
                    ],
                    'notify_url'=>$notify_url, //回调地址
                ],
                'headers' => [ 'Accept' => 'application/json']
            ]);
            //以上传参 均按照API列表下的App下单接口传参 其他支付可按照对应的接口进行传参
            $body = $resp->getBody();
            $resdata = json_decode($body->getContents(),true);

           //A生成App端唤起支付的数据
             $data = [
                        'appid'=>'',
                        'timestamp'=>time(),
                        'noncestr'=>$this->makeRandom(32),
                        'prepayid'=>$resdata['prepay_id']
                     ];
    
            $data['sign']= $test->dqsign($data); 
            $data['partnerid'] = 1;//商户号
            $data['package'] = 'Sign=WXPay'; //固定值
                       
            return ['error'=>0,'data'=>$data]; //0代表成功 data里的数据 就是App端要的数据
        }catch (RequestException $e) {
            // 进行错误处理
            if ($e->hasResponse()) {
                return  ['error'=>1,'msg'=>$e->getResponse()->getBody()];
            }
            return  ['error'=>2,'msg'=>$e->getMessage()];
        }
//以上按照App下单为例，其他的支付方式可参照微信支付对应的文档接口
```
## 获取平台证书
```php
$data = [
    'wx_mch_id'=>'1',//商户号
    'serial_no'=>'1',//api证书号(设置v3证书获得的)
    'wx_APIv3'=>'1',//APIv3密钥
    'wx_appid'=>'1', //微信appid
    'apiclient_key'=>__DIR__.'/wxpay_key/apiclient_key.pem', //API安全->申请证书 完成步骤获得的 apiclient_key.pem文件路径
];
WxPayFun\WxPayConfig::initConfig($data); //必须初始化
$test = new WxPayFun\WxPayFun();

/**
 * 获取/更新 证书和编号
 * @param string $filename 生成后证书文件保存路径 例如：www/wwwroot/wxce/wx_pay/   绝对路径,生成的证书文件名为cert.pem  
 * @return mixed 返回值为serial_no号
 */

 
 try{
     // 此方法建议放入定时任务种，执行时间不 大于 24 小时
     $serial_no = $test->filePutWxCert($filename);
 }catch (\Exception $e){
     //获取失败 错误均为data中数据不正确或者权限问题
 }
```

## 回调验证签名
```php
$data = [
    'cert'=>__DIR__.'/wxpay_key/cert.pem' //获取平台证书接口中获取的 平台证书文件路径
];
WxPayFun\WxPayConfig::initConfig($data); //必须初始化
$test = new WxPayFun\WxPayFun();
 /**
 * 验证回调主方法  verifySigns
 * @param $http_data header头的信息（微信回调发送的header头）可用getallheaders()获取
 * @param $body 应答主体信息 (微信发送的数据)(未解密的数据!!!) 一个json字符串 如果出现中文乱码，必须转义
 * @return int 验证成功返回 1  失败返回 0
 */
 $http_data = getallheaders();
 $info = file_get_contents("php://input"); //如果里面有中文乱码 必须转义
$red = $test->verifySigns($http_data,$body); //验证{$red}返回 1和0即可 
if($red == 1){
 //你的微信逻辑
}
```