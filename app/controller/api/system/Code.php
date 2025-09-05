<?php

namespace app\controller\api\system;

use api\sms\exception\SmsException;
use app\common\services\SmsService;
use app\controller\api\Base;
use app\helper\SnowFlake;
use think\captcha\facade\Captcha;
use think\facade\Cache;

class Code extends Base
{
    /**
     * 生成图形验证码
     */
    public function createVerifyCode()
    {
        $captcha = captcha1();
        $content = $captcha['image'];
        $uuid = SnowFlake::createOnlyId();
        Cache::store('redis')->set('catcha_' . $uuid, $captcha['code'], 300);
        return $this->success(['content' => 'data:image/jpg;base64,' . base64_encode($content), 'uuid' => $uuid]);
    }


    ##验证码
    public function check()
    {
        $param = $this->request->param(['uuid' => '', 'captcha' => '']);
        $uuid = $param['uuid'];
        $captcha = $param['captcha'];
        $code = Cache::store('redis')->get('catcha_' . $uuid);
        if ($code != $captcha) {
            return $this->error('验证失败');
        }
        Cache::store('redis')->delete('catcha_' . $uuid);
        return $this->success('');
    }

    ##验证码

    public function getToken()
    {
        return $this->success('', 'ok');
    }

    /**
     * 发送手机验证码
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendPhoneVerifyCode()
    {
        $phone = $this->request->param('mobile', '', 'trim');
        $type = $this->request->param('type', '', 'intval');

        try {
            SmsService::init($this->request->companyId)->sendVerifyCode($phone, $type);
        } catch (SmsException $e) {
            if ($e->getCode() == 1001) {
                return $this->error($e->getMessage(), 400);
            } else {
                return $this->error($e->getMessage());
            }
        }

        return $this->successText('发送成功');
    }

    /**
     * 发送手机验证码
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendPhoneVerifyCodeForCaptcha()
    {
        $phone = $this->request->param('mobile', '', 'trim');
        $type = $this->request->param('type', '', 'intval');
        $captcha = $this->request->param('captcha');
        $uuid = $this->request->param('uuid');
        if (!$captcha) {
            return $this->error('请输入图形验证码');
        }
        $code = Cache::store('redis')->get('catcha_' . $uuid);
        if ($code != $captcha) {
            return $this->error('验证失败');
        }
        Cache::store('redis')->delete('catcha_' . $uuid);

        try {
            SmsService::init($this->request->companyId)->sendVerifyCode($phone, $type);
        } catch (SmsException $e) {
            if ($e->getCode() == 1001) {
                return $this->error($e->getMessage(), 400);
            } else {
                return $this->error($e->getMessage());
            }
        }

        return $this->successText('发送成功');
    }
}