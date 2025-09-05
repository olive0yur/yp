<?php
declare (strict_types=1);

namespace app\listener\log;

class DingDingNotify
{
    public function handle($event)
    {
        try {
            // 开启钉钉错误通知
            if (env('log.ding_notify') === true) {
                $ding = new \bingher\ding\DingBot([
                    //自定义机器人api接口链接
                    'webhook' => env('log.ding_webhook'),
                    //签名密钥
                    'secret' => env('log.ding_secret')
                ]);
                foreach ($event->log as $k => $v) {
                    if ($k === 'error') {
                        foreach ($v as $k2 => $v2) {
                            $text = env('log.ding_title') ? env('log.ding_title') . PHP_EOL : '';
                            $text .= 'error: ' . $v2;
                            if (env('log.ding_at') === 'all') {
                                $ding->atAll()->text($text);
                            } else {
                                $ding->at(env('log.ding_at', ''))->text($text);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {

        }
    }
}
