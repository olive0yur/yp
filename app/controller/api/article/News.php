<?php

namespace app\controller\api\article;

use app\common\repositories\article\news\NewsCateRepository;
use app\common\repositories\article\news\NewsRepository;
use app\controller\api\Base;
use think\App;

class News extends Base
{
    protected $repository;

    public function __construct(App $app, NewsRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    /**
     * 新闻列表
     */
    public function getNewsList()
    {
        $where = $this->request->param(['cate_id'=>'', 'keywords'=>'', 'is_show' => 1]);
        [$page, $limit] = $this->getPage();
        return app('api_return')->success($this->repository->getList($where, $page, $limit, $this->request->companyId));
    }

    /**
     * 新闻列表
     */
    public function getNewsList1()
    {
        $where = $this->request->param(['cate_id'=>'', 'keywords'=>'', 'is_show' => 1,'uuid'=>$this->request->userId()]);
        [$page, $limit] = $this->getPage();
        return app('api_return')->success($this->repository->getList1($where, $page, $limit, $this->request->companyId));
    }

    /**
     * 分类列表
     */
    public function getCate()
    {
        /** @var NewsCateRepository $newsCateRepository */
        $newsCateRepository = app()->make(NewsCateRepository::class);
        return app('api_return')->success($newsCateRepository->getCateData($this->request->companyId, 1));
    }

    /**
     * 新闻详情
     */
    public function getDetail($id)
    {
        $res = $this->repository->getDetail($id);
        if($res) {
            return app('api_return')->success($res);
        } else {
            return app('api_return')->error('数据不存在');
        }
    }
}