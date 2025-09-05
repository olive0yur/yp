<?php

namespace api\sms;

use api\sms\driver\Juhe;
use api\sms\driver\Webchinese;
use api\sms\driver\Aliyun;
use api\sms\driver\yunMark;
use api\sms\exception\ErrorCode;
use api\sms\exception\SmsException;
use app\common\repositories\system\sms\SmsLogRepository;
use app\helper\Regular;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Request;

class Sms
{

    private $drive;
    private $phone;
    private $areaCode = '';
    private $config = [];

    /**
     * Sms constructor.
     */
    public function __construct($config = [])
    {
        $this->init($config);
    }

    /**
     * 初始化
     *
     * @return $this
     */
    public function init($config)
    {
        $this->config = $config;

        $drive = $this->config['sms_type'] ?? 1;
        if ($drive == 1) {
            $this->drive = new Aliyun($this->config);
        } else if ($drive == 2) {
            $this->drive = new Juhe($this->config);
        } else if ($drive == 3) {
            $this->drive = new Webchinese($this->config);
        } else if ($drive == 4) {
            $this->drive = new yunMark($this->config);
        }

        return $this;
    }

    /**
     * 设置国家区号
     *
     * @param string $areaCode 区号
     * @return $this
     */
    public function setAreaCode($areaCode)
    {
        $this->areaCode = $areaCode;

        return $this;
    }

    /**
     * 获取短信余额
     *
     * @return mixed
     */
    public function getSmsBalance()
    {
        $res = $this->drive->getSmsBalance();

        return $res;
    }

    /**
     * 测试发送
     *
     * @param string $phone 手机号
     * @param int $smsCodeType 短信类型
     * @return bool
     * @throws SmsException
     */
    public function sendTestContent($phone, $smsCodeType)
    {
        $this->setPhone($phone);
        switch ($smsCodeType) {
            case config('sms.sms_type.LOGIN_VERIFY_CODE'):
                $this->sendLoginVerifyCode();
                break;
            case config('sms.sms_type.MODIFY_MOBILE_VERIFY_CODE'):
                $this->sendModifyMobileVerifyCode();
                break;
            case config('sms.sms_type.MODIFY_PASSWORD'):
                $this->sendModifyPasswordVerifyCode();
                break;
            case config('sms.sms_type.REGISTER_VERIFY_CODE'):
                $this->sendRegisterVerifyCode();
                break;
            case config('sms.sms_type.REGISTER_SUCCESS'):
                $this->sendRegisterSuccessNotify('测试', '111111');
                break;
        }
        if ($this->drive->getSendStatus() === false) {
            throw new SmsException('发送失败');
        }

        return true;
    }

    /**
     * 设置手机号
     *
     * @param string $phone 手机好
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * 发送登陆验证码
     *
     * @return mixed
     */
    public function sendLoginVerifyCode()
    {
        $code = rand(100000, 999999);
        $params = [
            'code' => $code,
            'time' => $this->getSmsEffectiveTime()
        ];
        $content = $this->config['template'][config('sms.sms_type.LOGIN_VERIFY_CODE')]['content'] ?? '';
        $templateId = $this->config['template'][config('sms.sms_type.LOGIN_VERIFY_CODE')]['template_id'] ?? '';
        $content = $this->generateContent($content, $params);

        $res = $this->drive->sendSms($this->phone, [
            'content' => $content,
            'template_id' => $templateId,
            'params' => $params,
        ]);

        $this->addSmsLog(config('sms.sms_type.LOGIN_VERIFY_CODE'), $code);

        return $res;
    }

    /**
     * 获取短信有效时间
     *
     * @return int
     */
    public function getSmsEffectiveTime()
    {
        return intval(($this->config['auth_sms_time_out'] ?? 120) / 60);
    }

    /**
     * 生成内容
     *
     * @param $template
     * @param $params
     * @return array|mixed|string|string[]
     */
    public function generateContent($template, $params = [])
    {
        foreach ($params as $k => $v) {
            $template = str_replace('${' . $k . '}', $v, $template);
        }
        return $template;
    }

    /**
     * 添加短信日志
     *
     * @param string $smsType 短信类型
     * @param string $code 验证码
     * @return mixed
     */
    private function addSmsLog($smsType, $code = '')
    {
        $data = [
            'mobile' => $this->phone,
            'area_code' => $this->areaCode,
            'content' => $this->strToUtf8($this->drive->getContent()),
            'is_show' => 1,
//            'session_id' => sid(),
            'sms_type' => $smsType,
            'log_ip' => app('request')->ip(),
            'company_id' => $this->config['company_id'] ?? 0
        ];
        if ($this->drive->getSendStatus() === false) {
            $data['is_show'] = 2;
            $data['error_msg'] = $this->drive->getErrorMessage();
        }
        if ($code) {
            $data['code'] = $code;
        }
        /** @var SmsLogRepository $smsLogRepository */
        $smsLogRepository = app()->make(SmsLogRepository::class);
        return $smsLogRepository->create($data);
    }

    /**
     * 编码转成utf8
     *
     * @param string $str 要转换的字符
     * @return string
     */
    private function strToUtf8($str)
    {
        $encode = mb_detect_encoding($str, array("ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5'));
        if ($encode == 'UTF-8') {
            return $str;
        } else {
            return mb_convert_encoding($str, 'UTF-8', $encode);
        }
    }

    /**
     * 发送修改手机验证码
     *
     * @return mixed
     */
    public function sendModifyMobileVerifyCode()
    {
        $code = rand(100000, 999999);
        $params = [
            'code' => $code,
            'time' => $this->getSmsEffectiveTime()
        ];
        $content = $this->config['template'][config('sms.sms_type.MODIFY_MOBILE_VERIFY_CODE')]['content'] ?? '';
        $templateId = $this->config['template'][config('sms.sms_type.MODIFY_MOBILE_VERIFY_CODE')]['template_id'] ?? '';
        $content = $this->generateContent($content, $params);

        $res = $this->drive->sendSms($this->phone, [
            'content' => $content,
            'template_id' => $templateId,
            'params' => $params
        ]);

        $this->addSmsLog(config('sms.sms_type.MODIFY_MOBILE_VERIFY_CODE'), $code);

        return $res;
    }

    /**
     * 发送修改密码验证码
     *
     * @return mixed
     */
    public function sendModifyPasswordVerifyCode()
    {
        $code = rand(100000, 999999);
        $params = [
            'code' => $code,
            'time' => $this->getSmsEffectiveTime()
        ];
        $content = $this->config['template'][config('sms.sms_type.MODIFY_PASSWORD')]['content'] ?? '';
        $templateId = $this->config['template'][config('sms.sms_type.MODIFY_PASSWORD')]['template_id'] ?? '';
        $content = $this->generateContent($content, $params);

        $res = $this->drive->sendSms($this->phone, [
            'content' => $content,
            'template_id' => $templateId,
            'params' => $params
        ]);

        $this->addSmsLog(config('sms.sms_type.MODIFY_PASSWORD'), $code);

        return $res;
    }

    /**
     * 发送注册验证码
     *
     * @return mixed
     */
    public function sendRegisterVerifyCode()
    {
        $code = rand(100000, 999999);
        $params = [
            'code' => $code,
            'time' => $this->getSmsEffectiveTime()
        ];
        $content = $this->config['template'][config('sms.sms_type.REGISTER_VERIFY_CODE')]['content'] ?? '';
        $templateId = $this->config['template'][config('sms.sms_type.REGISTER_VERIFY_CODE')]['template_id'] ?? '';
        $content = $this->generateContent($content, $params);

        $res = $this->drive->sendSms($this->phone, [
            'content' => $content,
            'template_id' => $templateId,
            'params' => $params
        ]);

        $this->addSmsLog(config('sms.sms_type.REGISTER_VERIFY_CODE'), $code);

        return $res;
    }

    /**
     * 发送账号注册成功通知
     *
     * @return mixed
     */
    public function sendRegisterSuccessNotify($username = '', $password = '')
    {
        $params = [
            'username' => $username,
            'password' => $password
        ];
        $content = $this->config['template'][config('sms.sms_type.REGISTER_SUCCESS')]['content'] ?? '';
        $templateId = $this->config['template'][config('sms.sms_type.REGISTER_SUCCESS')]['template_id'] ?? '';
        $content = $this->generateContent($content, $params);
        $res = $this->drive->sendSms($this->phone, [
            'content' => $content,
            'template_id' => $templateId,
            'params' => $params
        ]);
        $this->addSmsLog(config('sms.sms_type.REGISTER_SUCCESS'));
        return $res;
    }

    /**
     * 发送手机验证码
     *
     * @param string $phone 手机号
     * @param int $smsCodeType 验证码类型
     * @return bool
     * @throws SmsException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sendVerifyCode($phone, $smsCodeType)
    {

        if ($this->config['verify_img_code'] == 1) {
            $verifyCode = Request::param('verify_code', '');
            if (!captcha_check($verifyCode)) {
                throw new SmsException('图形验证码错误', ErrorCode::PARAM_ERROR);
            }
        }
        if (!in_array($smsCodeType, config('sms.sms_type'))) {
            throw new SmsException('类型错误', ErrorCode::PARAM_ERROR);
        }
        if (!Regular::mobileVerify($phone)) {
            throw new SmsException('手机号格式错误', ErrorCode::PARAM_ERROR);
        }
        /** @var SmsLogRepository $smsLogRepository */
        $smsLogRepository = app()->make(SmsLogRepository::class);
        // 短信发送日志
        $smsLogInfo = $smsLogRepository->search($this->config['company_id'] ?? 0, [
            'mobile' => $phone,
            'is_verify' => 2,
        ])->where('is_show', 1)->order('id', 'desc')->find();

        $this->setPhone($phone);
        // 获取时间配置
        $smsTimeOut = (int)$this->config['send_sms_time_out'] ?? 120;

        if ($smsLogInfo && (time() - strtotime($smsLogInfo['add_time'])) < $smsTimeOut) {
            throw new ValidateException(($smsTimeOut / 60) . '分钟内不允许重复发送');
        }

        switch ($smsCodeType) {
            case config('sms.sms_type.LOGIN_VERIFY_CODE'):
                $this->sendLoginVerifyCode();
                break;
            case config('sms.sms_type.MODIFY_MOBILE_VERIFY_CODE'):
                $this->sendModifyMobileVerifyCode();
                break;
            case config('sms.sms_type.MODIFY_PASSWORD'):
                $this->sendModifyPasswordVerifyCode();
                break;
            case config('sms.sms_type.REGISTER_VERIFY_CODE'):
                $this->sendRegisterVerifyCode();
                break;
            case config('sms.sms_type.REGISTER_SUCCESS'):
                $this->sendRegisterSuccessNotify();
                break;
        }
        if ($this->drive->getSendStatus() === false) {
            throw new SmsException('发送失败');
        }

        return true;
    }

    /**
     * 验证码验证
     *
     * @param string $phone 手机号
     * @param string $smsVerifyCode 验证码
     * @param int $smsType 验证码类型
     * @return bool
     * @throws SmsException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkVerifyCode($phone, $smsVerifyCode, $smsType)
    {
        /** @var SmsLogRepository $smsLogRepository */
        $smsLogRepository = app()->make(SmsLogRepository::class);
        // 短信发送日志

        $smsLogInfo = $smsLogRepository->getWhere([
            'company_id' => $this->config['company_id'] ?? 0,
            'mobile' => $phone,
            'code' => $smsVerifyCode,
            'sms_type' => $smsType,
            'is_verify' => 2
        ]);

        $redis = Cache::store('redis');
        $cacheFail = $redis->get('fail_login_' . $phone);
        if ($cacheFail >= 3) {
            throw new SmsException('尝试次数过多，等会儿再来吧', ErrorCode::VERIFY_FAILED);
        }

        if (empty($smsLogInfo)) {
            //记录错误次数 延长有效时间
            if ($cacheFail) {
                $redis->inc('fail_login_' . $phone);
            } else {
                $redis->set('fail_login_' . $phone, 1, 180);
            }
            throw new SmsException('手机验证码不匹配', ErrorCode::VERIFY_FAILED);
        }

        $smsTimeOut = (int)$this->config['auth_sms_time_out'] ?? 120;
        if ((time() - strtotime($smsLogInfo['add_time'])) > $smsTimeOut) {
            throw new SmsException('手机验证码超时', ErrorCode::VERIFY_FAILED);
        }

        $smsLogRepository->update($smsLogInfo['id'], [
            'is_verify' => 1,
            'valid_time' => time()
        ]);

        return true;
    }

}
