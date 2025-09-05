<?php

namespace app\controller\api\gashapon;

use app\common\model\system\PaymentModel;
use app\common\repositories\gashapon\GashaponUserRepository;
use app\common\repositories\guild\GuildWareHouseRepository;
use app\common\repositories\guild\GuildWareLogRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\guild\GuildRepository;
use app\common\repositories\pool\PoolTransferLogRepository;
use app\common\repositories\system\PaymentRepository;
use think\App;
use think\Exception;
use think\facade\Cache;
use app\controller\api\Base;
use app\common\repositories\pool\PoolFlowRepository;
use \app\common\repositories\users\UsersPoolRepository;
use think\facade\Db;
use think\facade\Log;
use think\Request;

class Show extends Base
{

    /**
     * 开启列表
     */
    public function getInfo(GashaponUserRepository $repository){
        [$page,$limit] = $this->getPage();
        $param = $this->request->param([]);
        $param['uuid'] = $this->request->userId();
        return $this->success($repository->getApiList($param,$page,$limit,$this->request->companyId));
    }

    /**
     * 开启
     */
    public function open(GashaponUserRepository $repository)
    {
        return $this->success($repository->open($this->request->userInfo(),$this->request->companyId));
    }

    /**
     * 领取
     */
    public function create(GashaponUserRepository $repository)
    {
        $param = $this->request->param(['id'=>'']);
        if(!$param['id']) return $this->error('请选择领取');
        return $this->success($repository->create($param['id'],$this->request->userInfo(),$this->request->companyId));
    }

    /**
     * 弹幕
     */
    public function ofList(GashaponUserRepository $repository)
    {
        return $this->success($repository->ofList($this->request->userInfo(),$this->request->companyId));
    }

    public function apifox(GashaponUserRepository $repository)
    {
        return $this->success($repository->apifox($this->request->userInfo(),$this->request->companyId));
    }

}