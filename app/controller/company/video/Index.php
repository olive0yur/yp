<?php

namespace app\controller\company\video;

use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\video\VideoTaskRepository;
use think\App;
use think\exception\ValidateException;
use think\facade\Cache;
use app\controller\company\Base;
use app\validate\pool\SaleValidate;
use app\common\repositories\users\UsersRepository;
use app\common\repositories\pool\PoolModeRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\union\UnionAlbumRepository;
use app\common\repositories\union\UnionBrandRepository;

class Index extends Base
{
    protected $repository;

    public function __construct(App $app, VideoTaskRepository $repository)
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
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count']]);
        }
        return $this->fetch('video/index/list', [
            'addAuth' => company_auth('companyVideoTaskAdd'),
            'editAuth' => company_auth('companyVideoTaskEdit'),
            'delAuth' => company_auth('companyVideoTaskDel'),
        ]);
    }

    /**
     *
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'name' => '',
                'num' => '',
                'tips' => '',
                'amount'=>'',
                'mode'=>'',
            ]);
            try {
                $res = $this->repository->addInfo($this->request->companyId, $param);
                if ($res) {
                    admin_log(3, '添加广告任务', $param);
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {

            return $this->fetch('video/index/add');
        }
    }

    /**
     * 编辑
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
                'name' => '',
                'num' => '',
                'tips' => '',
                'amount'=>'',
                'mode'=>'',
            ]);
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('video/index/edit', ['info' => $info]);
        }
    }
    /**
     * 删除
     */
    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                admin_log(4, '删除广告任务 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}