<?php

namespace app\jobs;

use app\common\services\ExportService;
use app\helper\Log;
use think\queue\Job;

/**
 * 导出文件
 * Class ExportFileJob
 *
 * @package app\job
 */
class ExportFileJob
{

    public function writeLog($str)
    {
        Log::writeLog('导出文件', $str, true);
    }

    public function fire(Job $job, $data)
    {
        /** @var ExportService $exportService */
        $exportService = app()->make(ExportService::class);
        $this->writeLog('开始导出文件 id:' . $data['export_file_id']);
        $startTime = microtime(true);
        try {
            $exportService->generateFileByFileId($data['export_file_id'], $data['data']);
            $this->writeLog('文件导出成功 id:' . $data['export_file_id'] . ', 耗时: ' . round(microtime(true) - $startTime, 3) . '秒');
        } catch (\Exception $e) {
            $this->writeLog('文件导出失败 id:' . $data['export_file_id'] . ', msg:' . $e->getMessage());
            exception_log('导出文件失败', $e);
        }
        $job->delete();

    }

    public function failed($data)
    {
        // ...任务达到最大重试次数后，失败了
    }
}