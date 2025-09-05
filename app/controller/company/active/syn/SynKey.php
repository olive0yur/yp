<?php

namespace app\controller\company\active\syn;

use app\common\repositories\active\syn\ActiveSynKeyRepository;
use app\controller\company\Base;
use think\App;
use think\facade\Cache;

class SynKey extends Base
{
    protected $repository;

    public function __construct(App $app, ActiveSynKeyRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function list()
    {

        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'syn_id'=>''
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }
        return $this->fetch('/active/syn/synKey/list', [
            'syn_id' => $this->request->param('syn_id'),
            'addAuth' => company_auth('companySynMateriaAdd'),
            'editAuth' => company_auth('companySynMateriaEdit'),
            'delAuth' => company_auth('companySynMateriaDel'),
            'synAuth'=> company_auth('companySynMateriaInfoList'),
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
                'syn_id' => '',
                'type_num' => '',
                'num' => '',
            ]);
            if(!$param['type_num'] || $param['type_num'] <= 0 ) $this->error('请输入合成材料种类!');
            if(!$param['num'] || $param['num'] <= 0 ) $this->error('请输入合成材料数量!');
            if(!$param['title']) return $this->error('请输入合成标题');
            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                if ($res) {
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('active/syn/synKey/add');
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
                'title' => '',
                'type_num' => '',
                'num' => '',
            ]);
            if(!$param['type_num'] || $param['type_num'] <= 0 ) $this->error('请输入合成材料种类!');
            if(!$param['num'] || $param['num'] <= 0 ) $this->error('请输入合成材料数量!');
            if(!$param['title']) return $this->error('请输入合成标题');
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

            return $this->fetch('active/syn/synKey/add', [
                'info' => $info,

            ]);
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