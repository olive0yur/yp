<?php
declare (strict_types=1);

namespace app\listener\agent;


use app\common\repositories\users\user\AgentUserRepository;

class LogoutSuccess
{
    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {

        /**
         * @var AgentUserRepository $repository
         */
        $repository = app()->make(AgentUserRepository::class);
        $repository->clearSessionInfo();
    }
}
