<?php

namespace app\controller\company\poster;

use app\common\repositories\poster\PosterSiteRepository;
use app\validate\poster\PosetrSiteValidate;
use app\controller\company\Base;
use think\App;

class PosterSite extends Base
{
    protected $repository;

    public function __construct(App $app, PosterSiteRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0,'data' => $data['list'],'count' => $data['count'] ]);
        }
        return $this->fetch('poster/site/list', [
            'addAuth' => company_auth('companyPosterSiteAdd'),
            'editAuth' => company_auth('companyPosterSiteEdit'),
            'delAuth' => company_auth('companyPosterSiteDel')
        ]);
    }

    /**
     * 添加广告位置
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'site_name' => '',
                'day_price' => '',
                'week_price' => '',
                'month_price' => '',
                'year_price' => ''
            ]);
            if(!$param['site_name']) {
                return $this->error('广告位置不能为空');
            }
            if ($this->repository->companyFieldExists($this->request->companyId, 'site_name', $param['site_name'])) {
                return $this->error($param['site_name'] . '已存在');
            }
            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                if ($res) {
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            return $this->fetch('poster/site/add');
        }
    }

    /**
     * 编辑广告位置
     */
    public function edit()
    {
        $id = $this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->get($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'site_name' => '',
                'day_price' => '',
                'week_price' => '',
                'month_price' => '',
                'year_price' => ''
            ]);
            if(!$param['site_name']) {
                return $this->error('广告位置不能为空');
            }
            if ($this->repository->companyFieldExists($this->request->companyId, 'site_name', $param['site_name'], $id)) {
                return $this->error($param['site_name'] . '已存在');
            }
            try {
                $res = $this->repository->editInfo($id, $param);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络失败');
            }
        }
        return $this->fetch('poster/site/edit', [
            'info' => $info
        ]);
    }

    /**
     * 删除广告位置
     */
    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                admin_log(4, '删除广告位置 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}