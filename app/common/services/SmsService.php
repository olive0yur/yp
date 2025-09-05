<?php

namespace app\common\services;

use api\sms\exception\ErrorCode;
use api\sms\exception\SmsException;
use api\sms\Sms;
use app\common\repositories\system\sms\SmsConfigRepository;
use app\common\repositories\system\sms\SmsTemplateRepository;

class SmsService
{

    /**
     * 初始化
     * @param int $companyId 企业ID
     * @return Sms
     * @throws SmsException
     */
    public static function init(int $companyId = 0)
    {
        /** @var SmsConfigRepository $smsConfigRepository */
        $smsConfigRepository = app()->make(SmsConfigRepository::class);
        $smsConfig = $smsConfigRepository->getSmsConfig($companyId);
        if (empty($smsConfig)) {
            throw new SmsException('请在后台设置短信参数', ErrorCode::PARAM_ERROR);
        }
        /** @var SmsTemplateRepository $smsTemplateRepository */
        $smsTemplateRepository = app()->make(SmsTemplateRepository::class);
        $templateList = $smsTemplateRepository->getTemplateList($companyId);
        $template = [];
        foreach ($templateList as $k => $v) {
            $template[$v['sms_type']] = [
                'content' => $v['content'],
                'template_id' => $v['template_id']
            ];
        }
        $smsConfig['template'] = $template;
        return (new Sms($smsConfig));
    }
}