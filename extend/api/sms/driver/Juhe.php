<?php

namespace api\sms\driver;

class Juhe
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
        '10001' => '错误的请求KEY',
        '10002' => '该KEY无请求权限',
        '10003' => 'KEY过期',
        '10004' => '错误的OPENID',
        '10005' => '应用未审核超时，请提交认证',
        '10007' => '未知的请求源',
        '10008' => '被禁止的IP',
        '10009' => '被禁止的KEY',
        '10011' => '当前IP请求超过限制',
        '10012' => '请求超过次数限制',
        '10013' => '测试KEY超过请求限制',
        '10014' => '系统内部异常',
        '10020' => '接口维护',
        '10021' => '接口停用',
        '205402' => '错误的短信模板ID',
        '205403' => '网络错误,请重试',
        '205404' => '发送失败，具体原因请参考返回reason',
        '205405' => '号码异常/同一号码发送次数过于频繁',
        '205406' => '不被支持的模板',
        '205407' => '批量发送号码超限',
        '205408' => '批量发送库存次数不足',
        '205409' => '系统繁忙，请稍后重试',
        '205410' => '请求方法错误'
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
        return 0;
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
            'key' => $this->config['juhe_key'] ?? '',
            'mobile' => $phone,
            'tpl_id' => $data['template_id'],
            'vars' => json_encode($data['params'], JSON_UNESCAPED_UNICODE)
        ];

        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'http://v.juhe.cn/sms/send', [
            'form_params' => $requestParams
        ]);
        $params = json_decode($response->getBody(), true);
        $this->setSendStatus($params['error_code'] === 0);
        if ($params['error_code'] !== 0) {
            $this->setErrorMessage($this->errorCode[$params['error_code']] ?? '');
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