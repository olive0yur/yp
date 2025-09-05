<?php

namespace app\controller\company\box;

use think\App;
use app\controller\company\Base;
use app\common\repositories\union\UnionBrandRepository;

class Brand extends Base
{
    protected $repository;

    public function __construct(App $app, UnionBrandRepository $repository)
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
            $where['is_type'] = 2;
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }
        return $this->fetch('box/brand/list', [
            'addAuth' => company_auth('companyBindBoxSaleBrandAdd'),
            'editAuth' => company_auth('companyBindBoxSaleBrandEdit'),
            'delAuth' => company_auth('companyBindBoxSaleBrandDel'),
        ]);
    }

    /**
     * 添加广告
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'sort' => '',
                'name' => '',
                'cover' => '',
                'content' => '',
                'head_img' => '',
            ]);
            $param['is_type'] = 2;
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
            return $this->fetch('box/brand/add');
        }
    }

    /**
     * 编辑广告
     */
    public function edit()
    {
        $id = $this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->getDetail($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'sort' => '',
                'name' => '',
                'cover' => '',
                'content' => '',
                'head_img' => '',
            ]);
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络失败');
            }
        } else {
            return $this->fetch('box/brand/edit', [
                'info' => $info,

            ]);
        }
    }

    /**
     * 删除广告
     */
    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                admin_log(4, '删除肓盒品牌 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}