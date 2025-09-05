<?php

namespace app\common\services;

use EasyWeChat\Factory;

class WxWorkService
{

    public static function init()
    {
        $config = [
            'debug'  => false,
            'corp_id' => '',
            'agent_id' => '', // 如果有 agend_id 则填写
            'secret' => '',

            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',

            'log' => [
                'level' => 'debug',
                'file' => app()->getRuntimePath() . 'wechat.log',
            ],
        ];

        return Factory::work($config);
    }

}