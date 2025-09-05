<?php

namespace app\controller\api\article;

use app\common\repositories\article\guide\OperateCateRepository;
use app\common\repositories\article\guide\OperateRepository;
use app\controller\api\Base;
use think\App;

class Operate extends Base
{
    protected $repository;

    public function __construct(App $app, OperateRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }
    /**
     * 我的页面推荐列表
     */
    public function getSellList()
    {
        $where = $this->request->param(['is_tips'=>1, 'is_show' => 1]);
        [$page, $limit] = $this->getPage();
        return app('api_return')->success($this->repository->getSellList($where, $page, $limit, $this->request->companyId));
    }
    /**
     * 操作指南列表
     */
    public function getOperateList()
    {
        $where = $this->request->param(['cate_id', 'keywords', 'is_show' => 1]);
        [$page, $limit] = $this->getPage();
        return app('api_return')->success($this->repository->getList($where, $page, $limit, $this->request->companyId));
    }

    /**
     * 操作指南分类列表
     */
    public function getCate()
    {
        /** @var OperateCateRepository $operateCateRepository */
        $operateCateRepository = app()->make(OperateCateRepository::class);
        return app('api_return')->success($operateCateRepository->getCateData($this->request->companyId, 1));
    }

    /**
     * 操作指南分类详情
     */
    public function getDetail($id)
    { 
        $res = $this->repository->getDetail($id);
        if($res) {
            return app('api_return')->success($res->hidden(['is_show', 'file', 'edit_time']));
        } else {
            return app('api_return')->error('数据不存在');
        }
    }
}