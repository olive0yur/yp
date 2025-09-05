<?php

namespace api\sms\driver;

class Webchinese
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
    private $errorCode = [
        '-1' => '没有该用户账户',
        '-2' => '接口密钥不正确',
        '-21' => 'MD5接口密钥加密不正确',
        '-3' => '短信数量不足',
        '-11' => '该用户被禁用',
        '-14' => '短信内容出现非法字符',
        '-4' => '手机号格式不正确',
        '-41' => '手机号码为空',
        '-42' => '短信内容为空',
        '-51' => '短信签名格式不正确',
        '-52' => '短信签名太长建议签名10个字符以内',
        '-6' => 'IP限制'
    ];


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
        $requestParams = [
            'Action' => 'SMS_Num',
            'Uid' => $this->config['webchinese_sms_account'],
            'Key' => $this->config['webchinese_sms_key'],
        ];
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'http://www.smschinese.cn/web_api/SMS/', [
            'form_params' => $requestParams
        ]);
        $params = json_decode($response->getBody(), true);

        return $params;
    }

    /**
     * 发送短信
     *
     * @return mixed
     */
    public function sendSms($phone, $data)
    {
        $this->setContent($data['content']);
        $requestParams = [
            'Uid' => $this->config['webchinese_sms_account'] ?? '',
            'Key' => $this->config['webchinese_sms_key'] ?? '',
            'smsMob' => $phone,
            'smsText' => $data['content']
        ];

        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'http://utf8.api.smschinese.cn/', [
            'form_params' => $requestParams
        ]);
        $params = json_decode($response->getBody(), true);
        $this->setSendStatus($params === 1);
        if ($params != 1) {
            $this->setErrorMessage($this->errorCode[$params] ?? '');
        }

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