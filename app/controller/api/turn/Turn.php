<?php

namespace app\controller\api\turn;

use app\common\repositories\turntable\TurntableGoodsRepository;
use app\common\repositories\turntable\UsersTurnRepository;
use app\controller\api\Base;
use think\App;
use think\facade\Cache;

class Turn extends Base
{
      public function getConf(){
          $web = web_config($this->request->companyId,'turn');
          $pool = Cache::store('redis')->get('pool');
          if(!$pool) $pool = Cache::store('redis')->inc('pool',$web['pool']);
          $web['pool'] = $pool;
          return $this->success($web);
      }

    public function getList(TurntableGoodsRepository $repository){
        return $this->success($repository->getApiList($this->request->companyId));
    }
      public function getEnd(TurntableGoodsRepository $repository){
          $data = $this->request->param(['num'=>'','types'=>'']);
          if(!in_array($data['types'],[1,2])) return $this->error('参数错误!');
          if(!in_array($data['num'],[1,10])) return $this->error('数量错误!');
          return $this->success($repository->getEnd($data,$this->request->userInfo(),$this->request->companyId));
      }

    public function getUserTurn(UsersTurnRepository $repository){
        $data = $this->request->param(['page'=>'','limit'=>'']);
        return $this->success($repository->getApiList($data['page'],$data['limit'],$this->request->userInfo(),$this->request->companyId));
    }
}