<?php

namespace app\controller\company\active;

use app\common\repositories\active\ActiveRepository;
use app\common\repositories\active\draw\ActiveDrawRepository;
use app\common\repositories\active\syn\ActiveSynRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\controller\company\Base;
use think\App;
use think\facade\Cache;

class Task extends Base
{
    protected $repository;

    public function __construct(App $app, ActiveRepository $repository)
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
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }
        return $this->fetch('/active/active/list', [
            'addAuth' => company_auth('companyActiveAdd'),
            'editAuth' => company_auth('companyActiveEdit'),
            'delAuth' => company_auth('companyActiveDel'),
            'switchStatusAuth' => company_auth('companyActiveSwitch'),
            'voteAuth' => company_auth('companyActiveVote'),
        ]);
    }

    /**
     * 添加广告
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'title' => '',
                'active_type' => '',
                'start_time'=>'',
                'end_time'=>'',
                'cover'=>'',
                'content'=>'',
                'with_id'=>'',
                'threshold'=>'',
                'goods_id'=>'',
            ]);
            if(!$param['title']) return $this->error('请输入活动标题');
            if(!$param['active_type']) return $this->error('请选择活动类型');
            if(!$param['cover']) return $this->error('请上传活动图片');
            if(!$param['start_time'] || !$param['end_time']) return $this->error('请选择活动开始结束时间！');
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
            $this->commons();
            return $this->fetch('active/active/add');
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
                'id'=>'',
                'title' => '',
                'active_type' => '',
                'start_time'=>'',
                'end_time'=>'',
                'cover'=>'',
                'content'=>'',
                'with_id'=>'',
                'threshold'=>'',
                'goods_id'=>'',
            ]);
            if(!$param['title']) return $this->error('请输入活动标题');
            if(!$param['active_type']) return $this->error('请选择活动类型');
            if(!$param['cover']) return $this->error('请上传活动图片');
            if(!$param['start_time'] || !$param['end_time']) return $this->error('请选择活动开始结束时间！');
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res !== false) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络失败');
            }
        } else {
            $this->commons();
            return $this->fetch('active/active/edit', [
                'info' => $info,

            ]);
        }
    }


    /**
     * 设置状态
     */
    public function status()
    {
        $id = (int)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'status' => '',
            ]);

            if (!$this->repository->exists($id)) {
                return $this->error('数据不存在');
            }
            try {
                $res = $this->repository->update($id, $param);
                admin_log(3, '修改活动状态 id:' . $id, $param);
                if ($res !== false) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
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
                admin_log(4, '删除合成 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}