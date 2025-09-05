<?php
declare (strict_types=1);

namespace app\listener\log;

use app\common\services\WxWorkService;
use EasyWeChat\Kernel\Messages\TextCard;
use EasyWeChat\Work\GroupRobot\Messages\Text;

class WxworkNotify
{
    public function handle($event)
    {
        try {
            // 开启企业微信错误通知
            if (env('log.wxwork_notify') === true) {
                foreach ($event->log as $k => $v) {
                    if ($k === 'error') {
                        foreach ($v as $k2 => $v2) {
                            $text = env('log.wxwork_title') ? env('log.wxwork_title') . PHP_EOL : '';
                            $text .= 'error: ' . $v2;
                            $text = new Text($text);
                            if (env('log.wxwork_at') === 'all') {
                                $text->mention('@all');
                            } else {
                                $text->mentionByMobile((array)explode(',', env('log.ding_at', '')));
                            }

                            WxWorkService::init()->group_robot_messenger->message($text)
                                ->toGroup(env('log.wxwork_webhook_key', ''))
                                ->send();
                        }
                    }
                }
            }
        } catch (\Exception $e) {

        }

    }
}
