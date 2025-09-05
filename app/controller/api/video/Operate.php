<?php

namespace app\controller\api\video;

use app\common\model\system\PaymentModel;
use app\common\repositories\guild\GuildWareHouseRepository;
use app\common\repositories\guild\GuildWareLogRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\guild\GuildRepository;
use app\common\repositories\pool\PoolTransferLogRepository;
use app\common\repositories\system\PaymentRepository;
use app\common\repositories\video\VideoTaskRepository;
use think\App;
use think\Exception;
use think\facade\Cache;
use app\controller\api\Base;
use app\common\repositories\pool\PoolFlowRepository;
use \app\common\repositories\users\UsersPoolRepository;
use think\facade\Db;
use think\facade\Log;
use think\Request;

class Operate extends Base
{

    public function getList(VideoTaskRepository $repository){
        [$page,$limit] = $this->getPage();
        return $this->success($repository->getApiList($page,$limit,$this->request->userInfo(),$this->request->companyId));
    }

    public function callback(VideoTaskRepository $repository)
    {
        return $this->success($repository->setCallback($_GET));
    }
}