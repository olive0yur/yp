<?php

namespace app\controller\company\article\news;

use think\App;
use app\controller\company\Base;
use think\exception\ValidateException;
use app\validate\article\news\NewsValidate;
use app\common\repositories\article\news\NewsRepository;
use app\common\repositories\article\news\NewsCateRepository;

class News extends Base
{
    protected $repository;

    public function __construct(App $app, NewsRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
        $this->assign([
            'addAuth' => company_auth('companyArticleNewsAdd'),##添加资讯
            'editAuth' => company_auth('companyArticleNewsEdit'),##编辑资讯
            'delAuth' => company_auth('companyArticleNewsDel'),##删除资讯
            'switchStatusAuth' => company_auth('companyArticleNewsSwitch'),##开启/关闭资讯
            'placedTopAuth' => company_auth('companyArticleNewsPlacedTop')##置顶资讯
        ]);
    }

    protected function commonParams()
    {
        /**
         * @var NewsCateRepository $newsCateRepository
         */
        $newsCateRepository = app()->make(NewsCateRepository::class);
        $cateData = $newsCateRepository->getCateData($this->request->companyId,1);
        $this->assign([
            'cateData' => $cateData,
        ]);
    }

    /**
     * 文章列表
     */
    public function list()
    {
        if($this->request->isAjax()) {
            $where = $this->request ->param([
                'keywords' => ''
            ]);
            [$page,$limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data([ 'code' => 0,'data' => $data['list'], 'count' => $data['count'] ]);
        }
        return $this->fetch('article/news/list');
    }

    /**
     * 添加文章
     */
    public function add()
    {
        if($this->request->isPost()){
            $param = $this->request->param([
                'sort' => '',
                'title' => '',
                'keywords' => '',
                'desc' => '',
                'is_show' => '',
                'is_top' => '',
                'picture' => '',
                'content' => '',
                'cate_id' => ''
            ]);
            try {
                validate(NewsValidate::class)->scene('add')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            try {
                $param['content'] = htmlspecialchars($param['content']);
                $res = $this->repository->addInfo($this->request->companyId,$param);
                company_user_log(4, '添加文章', $param);
                if($res) {
                    return $this->success('添加成功');
                } else{
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络失败'.$e->getMessage());
            }
        } else {
            $this->commonParams();
            return $this->fetch('article/news/add');
        }
    }

    /**
     * 编辑文章
     */

     public function edit()
     {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->getDetail($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'title' => '',
                'keywords' => '',
                'desc' => '',
                'is_show' => '',
                'is_top' => '',
                'picture' => '',
                'content' => '',
                'cate_id' => '',
            ]);
            try {
                validate(NewsValidate::class)->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            try {
                $param['content'] = htmlspecialchars($param['content']);
                $res = $this->repository->editInfo($id, $param,$info);
                if ($res) {
                    company_user_log(4, '编辑文章 id:' . $info['id'], $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            $this->commonParams();
            return $this->fetch('article/news/edit', [
                'info' => $info,
            ]);
        }
     }

    /**
     * 删除文章
     */
    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                company_user_log(4, '删除文章 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

    /**
     * 文章显示开关 
     */
    public function switchStatus()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status', 2) == 1 ? 1 : 2;
        $res = $this->repository->switchStatus($id, $status);
        if ($res) {
            company_user_log(4, '修改文章状态 id:' . $id, [
                'status' => $status
            ]);
            return $this->success('修改成功');
        } else {
            return $this->error('修改失败');
        }
    }

    /**
     * 文章显示置顶开关 
     */
    public function placedTop()
    {
        $id = $this->request->param('id');
        $placedTop = $this->request->param('statusTop', 2) == 1 ? 1 : 2;

        $res = $this->repository->placedTop($id, $placedTop);
        if ($res) {
            company_user_log(4, '修改文章置顶 id:' . $id, [
                'statusTop' => $placedTop
            ]);
            return $this->success('修改成功');
        } else {
            return $this->error('修改失败');
        }
    }

}