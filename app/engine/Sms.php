<?php
namespace Engine;
/*--------------------------------
功能:		HTTP接口 发送短信
修改日期:	2015-05-24
说明:		http://utf8.sms.webchinese.cn/?Uid=本站用户名&Key=接口安全秘钥&smsMob=手机号码&smsText=短信内容
状态:
-1 	没有该用户账户
-2 	接口密钥不正确 [查看密钥]不是账户登陆密码
-21 	MD5接口密钥加密不正确
-3 	短信数量不足
-11 	该用户被禁用
-14 	短信内容出现非法字符
-4 	手机号格式不正确
-41 	手机号码为空
-42 	短信内容为空
-51 	短信签名格式不正确
接口签名格式为：【签名内容】
-6 	IP限制
大于0 	短信发送数量
--------------------------------*/

class Sms
{
    private $user = "wujie";
    private $Key = "cbc59d96ffcc2f98ec0c";

    public function send($mobile, $content)
    {
        $Uid = $this->user;
        $Key = strtoupper(MD5($this->Key));
        $smsText = $this->_safe_replace($content);//内容
        $url = 'http://utf8.sms.webchinese.cn/';
        $file_contents = httpPost($url, array("Uid" => $Uid, "KeyMD5" => $Key, "smsMob" => $mobile, "smsText" => $smsText));
        return $file_contents > 0 ? true : false;
    }

    public function smsNum()
    {
        $Uid = $this->user;
        $url = 'http://sms.webchinese.cn/web_api/SMS/?Action=SMS_Num';
        return httpPost($url, array("Uid" => $Uid, "Key" => $this->Key));
    }


    /**
     * 安全过滤函数
     *
     * @param $string
     * @return string
     */
    private function _safe_replace($string)
    {
        $string = str_replace('%20', '', $string);
        $string = str_replace('%27', '', $string);
        $string = str_replace('%2527', '', $string);
        $string = str_replace('*', '', $string);
        $string = str_replace('"', '&quot;', $string);
        $string = str_replace("'", '', $string);
        $string = str_replace('"', '', $string);
        $string = str_replace(';', '', $string);
        $string = str_replace('<', '&lt;', $string);
        $string = str_replace('>', '&gt;', $string);
        $string = str_replace("{", '', $string);
        $string = str_replace('}', '', $string);
        $string = str_replace('\\', '', $string);
//        return urlencode($string);
        return $string;
    }

}

?>