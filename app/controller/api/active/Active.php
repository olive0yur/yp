<?php

namespace app\controller\api\active;

use app\common\repositories\active\ActiveRepository;
use app\common\repositories\forum\ForumVoteRepository;
use app\common\repositories\snapshot\SnapshotRepository;
use app\controller\api\Base;
use think\App;

class Active extends Base
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function getList(ActiveRepository $repository){
        $data = $this->request->param([
            'sort'=>'',
            'keywords'=>'',
            'active_type'=>'',
        ]);
        $data['status'] = 1;
        [$page, $limit] = $this->getPage();
        return $this->success($repository->getApiList($data,$page, $limit,$this->request->companyId));
    }
    public function getDetail(ActiveRepository $repository){
        $data = $this->request->param(['id'=>'']);
        if(!$data['id']) return $this->error('ID不能为空');
        return $this->success($repository->getApiDetail($data['id'],$this->request->userId()));
    }

    public function vote(ForumVoteRepository $repository){
        $data = $this->request->param(['id'=>'','vote_type'=>2]);
        return $this->success($repository->vote($data,$this->request->userInfo(),$this->request->companyId),'投票完成');
    }



}