<?php

namespace app\controller\api\active;

use app\common\repositories\active\draw\ActiveDrawLogRepository;
use app\common\repositories\active\draw\ActiveDrawRepository;
use app\common\repositories\active\syn\ActiveSynLogRepository;
use app\common\repositories\active\syn\ActiveSynRepository;
use app\controller\api\Base;
use think\App;

class Draw extends Base
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function getDetail(ActiveDrawRepository $repository){
         $data = $this->request->param(['with_id'=>'','id'=>'']);
         return $this->success($repository->getApiDetail($data,$this->request->userId(),$this->request->companyId));
    }

    public function draw(ActiveDrawRepository $repository){
        $data = $this->request->param(['with_id'=>'','num'=>'']);
        if(!$data['with_id']) return $this->error('请选择抽奖活动');
        return $this->success($repository->draw($data['with_id'],$data['num'],$this->request->userInfo()->toArray(),$this->request->companyId),'抽奖完成');
    }

    public function getLog(ActiveDrawLogRepository $repository){
        $data = $this->request->param(['limit'=>'','page'=>'','uuid'=>$this->request->userId(),'with_id'=>'']);
        $data['draw_id'] = $data['with_id'];
        return $this->success($repository->getApiList($data,$data['page'],$data['limit'],$this->request->companyId));
    }

}