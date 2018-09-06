<?php
//require_once "./lib/WxPayApi.php";
Vendor('WxPay.lib.WxPayApi');
use Think\Cache;
/**
 *
 * JSAPI支付实现类
 * 该类实现了从微信公众平台获取code、通过code获取openid和access_token、
 * 生成jsapi支付js接口所需的参数、生成获取共享收货地址所需的参数
 *
 * 该类是微信支付提供的样例程序，商户可根据自己的需求修改，或者使用lib中的api自行开发
 *
 * @author widy
 *
 */
class JsApiPay
{
  /**
   *
   * 网页授权接口微信服务器返回的数据，返回样例如下
   * {
   *  "access_token":"ACCESS_TOKEN",
   *  "expires_in":7200,
   *  "refresh_token":"REFRESH_TOKEN",
   *  "openid":"OPENID",
   *  "scope":"SCOPE",
   *  "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
   * }
   * 其中access_token可用于获取共享收货地址
   * openid是微信支付jsapi支付接口必须的参数
   * @var array
   */
  public $data = null;

  public $curl_timeout = 30;

  public function GetQRcodeUrl($access_token ,$code){
    $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token;

    $this_header = array(
      "content-type: application/x-www-form-urlencoded;charset=UTF-8"
    );
    //初始化curl
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);
    curl_setopt($ch, CURLOPT_POST, 1);//设置为POST方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
      'action_name'=>'QR_LIMIT_SCENE',
      'action_info'=>array(
        'scene'=>array(
          'scene_id'=>$code
        )
      )
    )));//POST数据
    //设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if(WxPayConfig::CURL_PROXY_HOST != "0.0.0.0"
    && WxPayConfig::CURL_PROXY_PORT != 0){
      curl_setopt($ch,CURLOPT_PROXY, WxPayConfig::CURL_PROXY_HOST);
      curl_setopt($ch,CURLOPT_PROXYPORT, WxPayConfig::CURL_PROXY_PORT);
    }
    //运行curl，结果以jason形式返回
    $res = curl_exec($ch);
    curl_close($ch);
    //取出openid
    $data = json_decode($res,true);
    if($data && $data['ticket']){
      return 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$data['ticket'];
    }else{
      return false;
    }
  }

  public function GetJsTicket($from_url)
  {
    $ret = $this->GetAccessTokenFromMp();
    $access_token = $ret['access_token'];
    $url = $this->__CreateJsTicketUrl($access_token);
    //初始化curl
    $ch = curl_init();
    //设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if(WxPayConfig::CURL_PROXY_HOST != "0.0.0.0"
    && WxPayConfig::CURL_PROXY_PORT != 0){
      curl_setopt($ch,CURLOPT_PROXY, WxPayConfig::CURL_PROXY_HOST);
      curl_setopt($ch,CURLOPT_PROXYPORT, WxPayConfig::CURL_PROXY_PORT);
    }
    //运行curl，结果以jason形式返回
    $res = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($res,true);
    $jsTicket = $data['ticket'];
    $noncestr = 'Wm3WZYTPz0wzccnW';
    $timestamp = time();
    $url = $from_url;
    $result = array(
      'noncestr'=>$noncestr,
      'jsapi_ticket'=>$jsTicket,
      'timestamp'=>$timestamp,
      'url'=>$url
    );
    $runStr = 'jsapi_ticket='.$jsTicket.'&noncestr='.$noncestr.'&timestamp='.$timestamp.'&url='.$url;
    $signature = sha1($runStr);
    $result['signature'] = $signature;

    return $result;
  }

  public function GetAccessTokenFromMp()
  {
    $url = $this->__CreateAccesstokenUrl();
    //初始化curl
    $ch = curl_init();
    //设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if(WxPayConfig::CURL_PROXY_HOST != "0.0.0.0"
    && WxPayConfig::CURL_PROXY_PORT != 0){
      curl_setopt($ch,CURLOPT_PROXY, WxPayConfig::CURL_PROXY_HOST);
      curl_setopt($ch,CURLOPT_PROXYPORT, WxPayConfig::CURL_PROXY_PORT);
    }
    //运行curl，结果以jason形式返回
    $res = curl_exec($ch);
    curl_close($ch);
    //取出openid
    $data = json_decode($res,true);
    //access_token
    //expires_in , 单位秒
    return $data;
  }



  public function setMenu($content)
  {

    $ret = $this->GetAccessTokenFromMp();
    $access_token = $ret['access_token'];

    $url = $this->__CreateSetMenuUrl($access_token);
    $this_header = array(
      "content-type: application/x-www-form-urlencoded;charset=UTF-8"
    );
    //初始化curl
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);
    curl_setopt($ch, CURLOPT_POST, 1);//设置为POST方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $content);//POST数据
    //设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if(WxPayConfig::CURL_PROXY_HOST != "0.0.0.0"
    && WxPayConfig::CURL_PROXY_PORT != 0){
      curl_setopt($ch,CURLOPT_PROXY, WxPayConfig::CURL_PROXY_HOST);
      curl_setopt($ch,CURLOPT_PROXYPORT, WxPayConfig::CURL_PROXY_PORT);
    }
    //运行curl，结果以jason形式返回
    $res = curl_exec($ch);
    curl_close($ch);
    //取出openid
    $data = json_decode($res,true);
    //access_token
    //expires_in , 单位秒
    return $data;
  }

  public function sendMsgToUser($content)
  {

    $ret = $this->GetAccessTokenFromMp();
    $access_token = $ret['access_token'];

    $url = $this->__CreateSendMsgUrl($access_token);
    $this_header = array(
      "content-type: application/x-www-form-urlencoded;charset=UTF-8"
    );
    //初始化curl
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);
    curl_setopt($ch, CURLOPT_POST, 1);//设置为POST方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $content);//POST数据
    //设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if(WxPayConfig::CURL_PROXY_HOST != "0.0.0.0"
    && WxPayConfig::CURL_PROXY_PORT != 0){
      curl_setopt($ch,CURLOPT_PROXY, WxPayConfig::CURL_PROXY_HOST);
      curl_setopt($ch,CURLOPT_PROXYPORT, WxPayConfig::CURL_PROXY_PORT);
    }
    //运行curl，结果以jason形式返回
    $res = curl_exec($ch);
    curl_close($ch);
    //取出openid
    $data = json_decode($res,true);
    //access_token
    //expires_in , 单位秒
    return $data;
  }



  /**
   *
   * 通过跳转获取用户的openid，跳转流程如下：
   * 1、设置自己需要调回的url及其其他参数，跳转到微信服务器https://open.weixin.qq.com/connect/oauth2/authorize
   * 2、微信服务处理完成之后会跳转回用户redirect_uri地址，此时会带上一些参数，如：code
   *
   * @return 用户的openid
   */
  public function GetOpenid($encode_url  = null)
  {

    if(!$encode_url){
      $encode_url = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    }


    //通过code获得openid
    if (!isset($_GET['code'])){
      //触发微信返回code码
      $baseUrl = $encode_url;
//      Log::write('baseUrl:'.$baseUrl);
      $url = $this->__CreateOauthUrlForCode($baseUrl);
      Header("Location: $url");
      exit();
    } else {
      //获取code码，以获取openid
      $code = $_GET['code'];
      $openid = $this->getOpenidFromMp($code);
      return $openid;
    }
  }

  public function GetOpenidForPay($encode_url  = null)
  {

    if(!$encode_url){
      $encode_url = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    }


    //通过code获得openid
    if (!isset($_GET['code'])){
      //触发微信返回code码
      $baseUrl = $encode_url;
//      Log::write('baseUrl:'.$baseUrl);
      $url = $this->__CreateOauthUrlForCodeForPay($baseUrl);
      Header("Location: $url");
      exit();
    } else {
      //获取code码，以获取openid
      $code = $_GET['code'];
      $openid = $this->getOpenidFromMpForPay($code);
      return $openid;
    }
  }

  public function GetUserInfo($encode_url  = null){

    if(!$encode_url){
      $encode_url = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    }

    //通过code获得openid
    if (!isset($_GET['code'])){
      //触发微信返回code码
      $baseUrl = $encode_url;
//      Log::write('baseUrl:'.$baseUrl);
      $url = $this->__CreateOauthUrlForCode($baseUrl,'snsapi_userinfo');
      Header("Location: $url");
      exit();
    } else {
      //获取code码，以获取openid
      $code = $_GET['code'];

      $ret = $this->GetUserinfoFromMp($code);
      if(!$ret){
        $ret = $this->GetUserinfoFromMp($code);
      }
      return $ret;
    }
  }

  /**
   *
   * 获取jsapi支付的参数
   * @param array $UnifiedOrderResult 统一支付接口返回的数据
   * @throws WxPayException
   *
   * @return json数据，可直接填入js函数作为参数
   */
  public function GetJsApiParameters($UnifiedOrderResult)
  {
    if(!array_key_exists("appid", $UnifiedOrderResult)
    || !array_key_exists("prepay_id", $UnifiedOrderResult)
    || $UnifiedOrderResult['prepay_id'] == "")
    {
      throw new WxPayException("参数错误");
    }
    $jsapi = new WxPayJsApiPay();
    $jsapi->SetAppid($UnifiedOrderResult["appid"]);
    $timeStamp = time();
    $jsapi->SetTimeStamp("$timeStamp");
    $jsapi->SetNonceStr(WxPayApi::getNonceStr());
    $jsapi->SetPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);
    $jsapi->SetSignType("MD5");
    $jsapi->SetPaySign($jsapi->MakeSign());
    $parameters = json_encode($jsapi->GetValues());
    return $parameters;
  }

  /**
   *
   * 通过code从工作平台获取openid机器access_token
   * @param string $code 微信跳转回来带上的code
   *
   * @return openid
   */
  public function GetOpenidFromMp($code)
  {
    $url = $this->__CreateOauthUrlForOpenid($code);
    //初始化curl
    $ch = curl_init();
    //设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if(WxPayConfig::CURL_PROXY_HOST != "0.0.0.0"
    && WxPayConfig::CURL_PROXY_PORT != 0){
      curl_setopt($ch,CURLOPT_PROXY, WxPayConfig::CURL_PROXY_HOST);
      curl_setopt($ch,CURLOPT_PROXYPORT, WxPayConfig::CURL_PROXY_PORT);
    }
    //运行curl，结果以jason形式返回
    $res = curl_exec($ch);
    curl_close($ch);
    //取出openid
    $data = json_decode($res,true);
    $this->data = $data;
    $openid = $data['openid'];
    return $openid;
  }

  public function GetOpenidFromMpForPay($code)
  {
    $url = $this->__CreateOauthUrlForOpenidForPay($code);
    //初始化curl
    $ch = curl_init();
    //设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if(WxPayConfig::CURL_PROXY_HOST != "0.0.0.0"
    && WxPayConfig::CURL_PROXY_PORT != 0){
      curl_setopt($ch,CURLOPT_PROXY, WxPayConfig::CURL_PROXY_HOST);
      curl_setopt($ch,CURLOPT_PROXYPORT, WxPayConfig::CURL_PROXY_PORT);
    }
    //运行curl，结果以jason形式返回
    $res = curl_exec($ch);
    curl_close($ch);
    //取出openid
    $data = json_decode($res,true);
    $this->data = $data;
    $openid = $data['openid'];
    return $openid;
  }

  /*
   * 获取用户信息
   */
  private function _getUserinfo($access_token , $openid){

    $infoUrl = $this->__CreateUserinfoUrlForOpenid($access_token, $openid);
    $ch = curl_init();
    //设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
    curl_setopt($ch, CURLOPT_URL, $infoUrl);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if(WxPayConfig::CURL_PROXY_HOST != "0.0.0.0"
    && WxPayConfig::CURL_PROXY_PORT != 0){
      curl_setopt($ch,CURLOPT_PROXY, WxPayConfig::CURL_PROXY_HOST);
      curl_setopt($ch,CURLOPT_PROXYPORT, WxPayConfig::CURL_PROXY_PORT);
    }
    //运行curl，结果以jason形式返回
    $res = curl_exec($ch);
    curl_close($ch);

    //取出openid
    $info_data = json_decode($res,true);
    if($info_data && $info_data['openid']){
      return $info_data;
    }else{
      return false;
    }
  }

  public function GetUserinfoFromMp($code)
  {

    $openid = $this->GetOpenidFromMp($code);
    if(!$openid){
      $openid = $this->GetOpenidFromMp($code);
      if(!$openid){
        return false;
      }
    }
    $access_token = $this->data['access_token'];

    $Cache = Cache::getInstance('Redis');
    if($Cache->auth(C('REDIS_AUTH'))) {
      $ret = $Cache->mget(C('REDIS_USER_WEIXININFO_KEY_PREFIX').$openid);
      if($ret){
        return json_decode($ret,true);
      }
    }

    $info_data = $this->_getUserinfo($access_token, $openid);
    if(!$info_data){
      $info_data = $this->_getUserinfo($access_token, $openid);
    }else{
      if($Cache->auth(C('REDIS_AUTH'))) {
        $Cache->mset(C('REDIS_USER_WEIXININFO_KEY_PREFIX').$openid, json_encode($info_data));
      }
    }

    return $info_data;
  }

  /**
   *
   * 拼接签名字符串
   * @param array $urlObj
   *
   * @return 返回已经拼接好的字符串
   */
  private function ToUrlParams($urlObj)
  {
    $buff = "";
    foreach ($urlObj as $k => $v)
    {
      if($k != "sign"){
        $buff .= $k . "=" . $v . "&";
      }
    }

    $buff = trim($buff, "&");
    return $buff;
  }

  /**
   *
   * 获取地址js参数
   *
   * @return 获取共享收货地址js函数需要的参数，json格式可以直接做参数使用
   */
  public function GetEditAddressParameters()
  {
    $getData = $this->data;
    $data = array();
    $data["appid"] = WxPayConfig::APPID;
    $data["url"] = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    $time = time();
    $data["timestamp"] = "$time";
    $data["noncestr"] = "1234568";
    $data["accesstoken"] = $getData["access_token"];
    ksort($data);
    $params = $this->ToUrlParams($data);
    $addrSign = sha1($params);

    $afterData = array(
    "addrSign" => $addrSign,
    "signType" => "sha1",
    "scope" => "jsapi_address",
    "appId" => WxPayConfig::APPID,
    "timeStamp" => $data["timestamp"],
    "nonceStr" => $data["noncestr"]
    );
    $parameters = json_encode($afterData);
    return $parameters;
  }

  /**
   *
   * 构造获取code的url连接
   * @param string $redirectUrl 微信服务器回跳的url，需要url编码
   *
   * @return 返回构造好的url
   */
  private function __CreateOauthUrlForCode($redirectUrl, $scope_type='snsapi_base')
  {
    $urlObj["appid"] = WxPayConfig::APPID;
    $urlObj["redirect_uri"] = "$redirectUrl";
    $urlObj["response_type"] = "code";
    $urlObj["scope"] = $scope_type;
    $urlObj["state"] = "STATE"."#wechat_redirect";
    $bizString = $this->ToUrlParams($urlObj);
//    echo $bizString;exit;
    return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
  }

  private function __CreateOauthUrlForCodeForPay($redirectUrl, $scope_type='snsapi_base')
  {
    $urlObj["appid"] = WxPayConfig::PAY_APPID;
    $urlObj["redirect_uri"] = "$redirectUrl";
    $urlObj["response_type"] = "code";
    $urlObj["scope"] = $scope_type;
    $urlObj["state"] = "STATE"."#wechat_redirect";
    $bizString = $this->ToUrlParams($urlObj);
//    echo $bizString;exit;
    return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
  }

  /**
   *
   * 构造获取open和access_toke的url地址
   * @param string $code，微信跳转带回的code
   *
   * @return 请求的url
   */
  private function __CreateOauthUrlForOpenid($code)
  {
    $urlObj["appid"] = WxPayConfig::APPID;
    $urlObj["secret"] = WxPayConfig::APPSECRET;
    $urlObj["code"] = $code;


//    echo WxPayConfig::APPID.'-'.WxPayConfig::APPSECRET.'-'.$code;exit;

    $urlObj["grant_type"] = "authorization_code";
    $bizString = $this->ToUrlParams($urlObj);
    return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
  }

  private function __CreateOauthUrlForOpenidForPay($code)
  {
    $urlObj["appid"] = WxPayConfig::PAY_APPID;
    $urlObj["secret"] = WxPayConfig::PAY_APPSECRET;
    $urlObj["code"] = $code;


//    echo WxPayConfig::APPID.'-'.WxPayConfig::APPSECRET.'-'.$code;exit;

    $urlObj["grant_type"] = "authorization_code";
    $bizString = $this->ToUrlParams($urlObj);
    return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
  }

  private function __CreateJsTicketUrl($token)
  {
    $urlObj["access_token"] = $token;
    $urlObj["type"] = 'jsapi';
    $bizString = $this->ToUrlParams($urlObj);
    return "https://api.weixin.qq.com/cgi-bin/ticket/getticket?".$bizString;
  }

  /**
   * 构造获取access_toke的url地址
   * @return string
   */
  private function __CreateAccesstokenUrl()
  {
    $urlObj["appid"] = WxPayConfig::APPID;
    $urlObj["secret"] = WxPayConfig::APPSECRET;
    $urlObj["grant_type"] = "client_credential";
    $bizString = $this->ToUrlParams($urlObj);
    return "https://api.weixin.qq.com/cgi-bin/token?".$bizString;
  }

  private function __CreateSendMsgUrl($accesstoken)
  {
    $urlObj["access_token"] = $accesstoken;
    $bizString = $this->ToUrlParams($urlObj);
    return "https://api.weixin.qq.com/cgi-bin/message/custom/send?".$bizString;
  }

  private function __CreateSetMenuUrl($accesstoken)
  {
    $urlObj["access_token"] = $accesstoken;
    $bizString = $this->ToUrlParams($urlObj);
    return "https://api.weixin.qq.com/cgi-bin/menu/create?".$bizString;
  }

  /**
   *
   * 构造获取用户信息的url地址
   *
   * @param $access_token
   * @param $open_id
   * @return string
   */
  private function __CreateUserinfoUrlForOpenid($access_token, $open_id)
  {
    $urlObj["access_token"] = $access_token;
    $urlObj["openid"] = $open_id;
    $urlObj["lang"] = 'zh_CN';
    $bizString = $this->ToUrlParams($urlObj);
    return "https://api.weixin.qq.com/sns/userinfo?".$bizString;
  }
}
