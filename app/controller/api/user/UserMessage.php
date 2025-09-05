<?php

namespace app\controller\api\user;

use app\common\repositories\users\UsersMessageRepository;
use app\controller\api\Base;
use think\App;

class UserMessage extends Base
{
    protected $repository;

    public function __construct(App $app, UsersMessageRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function push()
    {
        $data = $this->request->param(['files'=>[],'content'=>'','title'=>'']);
        if(!$data['content']) return $this->error('反馈内容不能为空！');
        $data['uuid'] = $this->request->userId();
        return $this->success($this->repository->push($data,$this->request->userId(),$this->request->companyId),'发布成功');
    }

    public function getList(){
        $data = $this->request->param(['limit'=>'','page'=>'']);
        $data['uuid'] = $this->request->userId();
        return $this->success($this->repository->getApiList($data,$data['page'],$data['limit'],$this->request->companyId));
    }

}