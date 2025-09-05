<?php

namespace app\tasks;

use app\helper\Log;

abstract class Task extends \yunwuxin\cron\Task
{
    /**
     * 获取任务名
     *
     * @return string
     */
    abstract protected function getName(): string;

    /**
     * 执行任务
     *
     * @return mixed
     */
    abstract protected function exec();

    protected function execute()
    {
        $startTime = microtime(true);
        $this->writeLog('开始执行');

        $this->exec();

        $this->writeLog('执行成功，耗时: ' . round(microtime(true) - $startTime, 3) . '秒' . PHP_EOL);
    }

    public function writeLog($str)
    {
        Log::writeLog('计划任务', $this->getName() . ' ' . $str);
    }

}