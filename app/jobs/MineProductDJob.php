<?php

namespace app\jobs;

use app\common\model\mine\MineUserModel;
use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersRepository;
use think\queue\Job;

class MineProductDJob
{
    public function fire(Job $job, $data)
    {

        try {
            $this->ActiveSyn($data);
        } catch (\Exception $e) {
            dump($e);
            exception_log('任务处理失败', $e);
        }
        $job->delete();
    }
    /**
     * 矿洞生产
     * @param $data
     * @return mixed
     */
    public function ActiveSyn($data)
    {

    }
    public function failed($data)
    {
        // ...任务达到最大重试次数后，失败了
    }

}