<?php

namespace app\jobs;

class Queue
{
    /**
     * 添加队列
     *
     * @param string $job 执行脚本
     * @param array $data 数据
     * @param string $queue 队列名
     */
    public static function push($job, $data = '', $queue = null)
    {
        if (!$queue) {
            $queue = self::getQueueName();
        }
        return \think\facade\Queue::push($job, $data, $queue);
    }

    /**
     * 添加队列延迟执行
     *
     * @param int $delay 延迟时间（秒）
     * @param string $job 执行脚本
     * @param array $data 数据
     * @param string $queue 队列名
     */
    public static function later($delay, $job, $data = '', $queue = null)
    {
        if (!$queue) {
            $queue = self::getQueueName();
        }
        return \think\facade\Queue::later($delay, $job, $data, $queue);
    }

    /**
     * 获取进程名称
     *
     * @return string
     */
    public static function getQueueName()
    {
        return env('queue.queue_name', 'default');
    }
}