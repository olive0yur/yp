<?php

namespace app\controller\api\article;

use app\common\repositories\article\faq\FaqCateRepository;
use app\common\repositories\article\faq\FaqRepository;
use app\controller\api\Base;
use think\App;

class Faq extends Base
{
    protected $repository;

    public function __construct(App $app, FaqRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    /**
     * 常见问题列表
     */
    public function getFaqList()
    {
        $where = $this->request->param(['cate_id', 'keywords', 'is_show' => 1]);
        [$page, $limit] = $this->getPage();
        return app('api_return')->success($this->repository->getList($where, $page, $limit, $this->request->companyId));
    }

    /**
     * 常见问题分类列表
     */
    public function getCate()
    {
        /** @var FaqCateRepository $faqCateRepository */
        $faqCateRepository = app()->make(FaqCateRepository::class);
        return app('api_return')->success($faqCateRepository->getApiList($this->request->companyId));
    }

    /**
     * 常见问题详情
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