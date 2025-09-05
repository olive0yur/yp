<?php
declare (strict_types=1);

namespace app\helper;

use think\facade\Log as ThinkLog;

class Log
{
    /**
     * 写入日志
     *
     * @param string $fileName 文件名称
     * @param string $str 日志信息
     * @param bool $output 输出
     */
    public static function writeLog($fileName, $str, $output = false)
    {
        $dir = app()->getRootPath() . 'runtime/app_log';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (count(explode('.', $fileName)) == 1) {
            $fileName = $fileName . '.log';
        }
        list($t1, $t2) = explode(' ', microtime());

        $writeStr = date('Y-m-d H:i:s', (int)$t2) . ' ' . str_pad(bcmul($t1, '1000'), 3, '0', STR_PAD_LEFT) . ' ' . $str . PHP_EOL;
        file_put_contents($dir . '/' . $fileName, $writeStr, FILE_APPEND);
        if ($output) {
            echo $writeStr;
        }
    }

    /**
     * 自定义异常日志
     *
     * @param string $msg 错误信息
     * @param \Exception $exception 异常类
     * @return void
     */
    public static function exceptionWrite($msg, $exception)
    {
        $errorMsg = $msg . PHP_EOL;
        $errorMsg .= 'file: ' . $exception->getFile() . ' 第' . $exception->getLine() . '行' . PHP_EOL;
        $errorMsg .= 'error_msg: ' . $exception->getMessage() . PHP_EOL;

        $trace = 'trace: ' . PHP_EOL;
        $exceptionTrace = $exception->getTrace();
        $exceptionTrace = array_reverse($exceptionTrace);
        foreach ($exceptionTrace as $v) {
            $line = $v['line'] ?? 0;
            $file = $v['file'] ?? '';
            if ($file != '') {
                if ($line > 0) {
                    $trace .= 'file: ' . $v['file'] . ' 第' . $line . '行' . PHP_EOL;
                } else {
                    $trace .= 'file: ' . $v['file'] . PHP_EOL;
                }
            }
        }
        $errorMsg .= $trace;

        ThinkLog::write($errorMsg, 'error');
    }
}
