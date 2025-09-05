<?php

namespace app\controller\api\poster;

use app\common\repositories\poster\PosterRecordRepository;
use app\controller\api\Base;
use think\App;
use think\exception\ValidateException;

class Poster extends Base
{
    protected $repository;

    public function __construct(App $app, PosterRecordRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function getlist(){
        $data = $this->request->param([
            'site_id' => '',
            'is_show'=>1
        ]);
        [$page, $limit] = $this->getPage();
        return $this->success($this->repository->getWebList($data,$page, $limit,$this->request->companyId));
    }

}