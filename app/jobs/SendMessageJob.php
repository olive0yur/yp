<?php

namespace app\jobs;

use app\common\services\MessageService;
use think\queue\Job;

/**
 * 发送消息通知
 */
class SendMessageJob
{

    public function fire(Job $job, $data)
    {
        try {
            MessageService::sendMessage($data['message_type'], $data['params'] ?? []);
        } catch (\Exception $e) {
            exception_log('发送通知失败', $e);
        }
        $job->delete();

    }

    public function failed($data)
    {
        // ...任务达到最大重试次数后，失败了
    }

}