<?php

namespace api\sms\driver;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use think\facade\Log;

class yunMark
{

    // 短信内容
    private $content = '';
    // 发送状态
    private $sendStatus = false;
    // 错误信息
    private $errorMessage = '';
    // 配置
    private $config = [];

    // 错误码


    public function __construct($config = [])
    {
        $this->config = $config;
    }

    /**
     * 获取短信余额
     *
     * @return mixed
     */
    public function getSmsBalance()
    {
        return 0;
    }

    /**
     * 发送短信
     *
     * @return mixed
     */
    public function sendSms($phone, $data)
    {
        $host = 'https://api.smsbao.com/sms';
        $method = "GET";

        $querys = '?u=17689914081&p='.md5('Aa123123').'&g=&m=' . $phone . '&c=【宁德弥界文化】您的验证码是' . $data['params']['code'] . '。如非本人操作，请忽略本短信';
        $url = $host . $querys;

        // dd($url);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);  //如果只想获取返回参数，可设置为false
        if (1 == strpos("$" . $host, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $retult = json_decode(curl_exec($curl), true);
        $this->setSendStatus($retult == '0');


        return $this->getSendStatus();
    }

    /**
     * 设置短信内容
     *
     * @param string $content 短信内容
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * 获取短信内容
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * 设置发送状态
     *
     * @param bool $status 发送状态
     */
    public function setSendStatus($status)
    {
        $this->sendStatus = $status;
    }

    /**
     * 获取发送状态
     *
     * @return mixed
     */
    public function getSendStatus()
    {
        return $this->sendStatus;
    }

    /**
     * 设置错误信息
     *
     * @param string $message 错误信息
     */
    public function setErrorMessage($message)
    {
        $this->errorMessage = $message;
    }

    /**
     * 获取错误信息
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

}