<?php

namespace api\sms\driver;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use think\facade\Log;

class Aliyun
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
        'isv.SMS_SIGNATURE_SCENE_ILLEGAL' => '短信所使用签名场景非法',
        'isv.EXTEND_CODE_ERROR' => '扩展码使用错误，相同的扩展码不可用于多个签名',
        'isv.DOMESTIC_NUMBER_NOT_SUPPORTED' => '国际/港澳台消息模板不支持发送境内号码',
        'isv.DENY_IP_RANGE' => '源IP地址所在的地区被禁用',
        'isv.DAY_LIMIT_CONTROL' => '触发日发送限额',
        'isv.SMS_CONTENT_ILLEGAL' => '短信内容包含禁止发送内容',
        'isv.SMS_SIGN_ILLEGAL' => '签名禁止使用',
        'isp.RAM_PERMISSION_DENY' => 'RAM权限DENY',
        'isv.OUT_OF_SERVICE' => '业务停机',
        'isv.PRODUCT_UN_SUBSCRIPT' => '未开通云通信产品的阿里云客户',
        'isv.PRODUCT_UNSUBSCRIBE' => '产品未开通',
        'isv.ACCOUNT_NOT_EXISTS' => '账户不存在',
        'isv.ACCOUNT_ABNORMAL' => '账户异常',
        'isv.SMS_TEMPLATE_ILLEGAL' => '短信模版不合法',
        'isv.SMS_SIGNATURE_ILLEGAL' => '短信签名不合法',
        'isv.INVALID_PARAMETERS' => '参数异常',
        'isp.SYSTEM_ERROR' => 'isp.SYSTEM_ERROR',
        'isv.MOBILE_NUMBER_ILLEGAL' => '非法手机号',
        'isv.MOBILE_COUNT_OVER_LIMIT' => '手机号码数量超过限制',
        'isv.TEMPLATE_MISSING_PARAMETERS' => '模版缺少变量',
        'isv.BUSINESS_LIMIT_CONTROL' => '业务限流',
        'isv.INVALID_JSON_PARAM' => 'JSON参数不合法，只接受字符串值',
        'isv.BLACK_KEY_CONTROL_LIMIT' => '黑名单管控',
        'isv.PARAM_LENGTH_LIMIT' => '参数超出长度限制',
        'isv.PARAM_NOT_SUPPORT_URL' => '不支持URL',
        'isv.AMOUNT_NOT_ENOUGH' => '账户余额不足',
        'isv.TEMPLATE_PARAMS_ILLEGAL' => '模版变量里包含非法关键字',
        'SignatureDoesNotMatch' => 'Specified signature is not matched with our calculation.',
        'InvalidTimeStamp.Expired' => 'Specified time stamp or date value is expired.',
        'SignatureNonceUsed' => 'Specified signature nonce was used already.',
        'InvalidVersion' => 'Specified parameter Version is not valid.',
        'InvalidAction.NotFound' => 'Specified api is not found, please check your url and method',
        'isv.SIGN_COUNT_OVER_LIMIT' => '一个自然日中申请签名数量超过限制。',
        'isv.TEMPLATE_COUNT_OVER_LIMIT' => '一个自然日中申请模板数量超过限制。',
        'isv.SIGN_NAME_ILLEGAL' => '签名名称不符合规范。',
        'isv.SIGN_FILE_LIMIT' => '签名认证材料附件大小超过限制。',
        'isv.SIGN_OVER_LIMIT' => '签名字符数量超过限制。',
        'isv.TEMPLATE_OVER_LIMIT' => '签名字符数量超过限制。',
        'SIGNATURE_BLACKLIST' => '签名黑名单'
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
        try {
            AlibabaCloud::accessKeyClient($this->config['aliyun_access_key_id'] ?? '', $this->config['aliyun_access_key_secret'] ?? '')
                ->regionId('cn-hangzhou')
                ->asDefaultClient();
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'PhoneNumbers' => $phone,
                        'SignName' => $this->config['signature'],
                        'TemplateCode' => $data['template_id'],
                        'TemplateParam' => json_encode($data['params'], JSON_UNESCAPED_UNICODE),
                    ],
                ])
                ->request();
            $result = $result->toArray();
            $this->setSendStatus($result['Code'] == 'OK');
            if ($result['Code'] != 'OK') {
                $this->setErrorMessage($this->errorCode[$result['Code']] ?? '');
            }
        } catch (ClientException $e) {
            $this->setErrorMessage($e->getErrorMessage());
            Log::write('短信发送失败ClientException, request_param: ' . print_r($data, true) . 'error_msg: ' . $e->getErrorMessage(), 'error');
        } catch (ServerException $e) {
            $this->setErrorMessage($e->getErrorMessage());
            Log::write('短信发送失败ServerException, request_param: ' . print_r($data, true) . 'error_msg: ' . $e->getErrorMessage(), 'error');
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