<?php

namespace app\controller\company\active\syn;

use app\common\repositories\active\syn\ActiveSynInfoRepository;
use app\common\repositories\active\syn\ActiveSynRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\box\BoxSaleRepository;
use app\controller\company\Base;
use think\App;

class SynInfo extends Base
{


    protected $repository;

    public function __construct(App $app, ActiveSynInfoRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function commons(){
        $get = $this->request->get();
        if(!$get['syn_id']) return $this->error('请选择合成');
        if(!$get['is_target']) return $this->error('请选择类型');

        $activeSynInfoRepository = $this->app->make(ActiveSynInfoRepository::class);
        $get = $this->request->get();
        $count = $activeSynInfoRepository->search(['syn_id'=>$get['syn_id']],$this->request->companyId)->count('id');
        $activeSynRepository = $this->app->make(ActiveSynRepository::class);
        $syn = $activeSynRepository->search([],$this->request->companyId)->where('id',$get['syn_id'])->find();

        $poolSaleRepository = $this->app->make(PoolSaleRepository::class);
        $poolList = $poolSaleRepository->search(['is_number'=>1],$this->request->companyId)->field('id,title')->select()->toArray();


        $this->assign(['get'=>$get,'syn'=>$syn,'count'=>$count,'poolList'=>$poolList]);
    }

    public function list()
    {

        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'is_target'=>'',
                'syn_id'=>'',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }


        $this->commons();
        return $this->fetch('/active/syn/synInfo/list', [
            'addAuth' => company_auth('companyActiveSynTargetAdd'),
            'editAuth' => company_auth('companyActiveSynTargetEdit'),
            'delAuth' => company_auth('companyActiveSynTargetDel'),
            'people' => company_auth('companyFraudList'),

            'addSillAuth' => company_auth('companyActiveSynSillAdd'),
            'editSillAuth' => company_auth('companyActiveSynSillEdit'),
            'delSillAuth' => company_auth('companyActiveSynSillDel'),

            'addUserAuth' => company_auth('companyActiveSynUserAdd'),
            'delUserAuth' => company_auth('companyActiveSynUserDel'),
            'importFileAuth' => company_auth('companyActiveSynUserImport'),

            'authMateria' => company_auth('companySynMateriaList'),
            ]);
    }


    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'syn_id' => '',
                'syn_type'=>'',
                'is_target'=>'',
                'target_type'=>'',
                'goods_id' => '',
                'probability'=>'',
                'num'=>'',
                'img'=>''
            ]);
            try {
                $count = $this->repository->search(['syn_id'=>$this->request->get('syn_id'),'is_target'=>1,'syn_type'=>1],$this->request->companyId)->count('id');
                if($this->request->post('syn_type') == 1 && $count > 0) return $this->error('普通合成只能添加一种合成目标!');
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
            $this->commons();
            return $this->fetch('active/syn/synInfo/add');
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
                'syn_id' => '',
                'syn_type'=>'',
                'is_target'=>'',
                'goods_id' => '',
                'probability'=>'',
                'num'=>'',
                'target_type'=>'',
                'img'=>''
            ]);
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
            return $this->fetch('active/syn/synInfo/add', [
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
                admin_log(3, '修改合成状态 id:' . $id, $param);
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


    public function addMateria()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'syn_id' => '',
                'target_type'=>'',
                'syn_type'=>'',
                'is_target'=>'',
                'goods_id' => '',
                'num'=>'',
                'is_must'=>'',
            ]);

            try {
                $count = $this->repository->search(['syn_id'=>$param['syn_id'],'goods_id'=>$param['goods_id'],'is_target'=>2],$this->request->companyId)->count('id');
                if($count > 0) return $this->error('此材料已存在!');
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
            $this->commons();
            return $this->fetch('active/syn/synInfo/addMateria');
        }
    }

    /**
     * 编辑广告
     */
    public function editMateria()
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
                'syn_id' => '',
                'target_type'=>'',
                'syn_type'=>'',
                'is_target'=>'',
                'goods_id' => '',
                'num'=>'',
                'is_must'=>'',
            ]);
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络失败'.$e->getMessage());
            }
        } else {
            $this->commons();
            return $this->fetch('active/syn/synInfo/addMateria', [
                'info' => $info,
            ]);
        }
    }


    public function addSill()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'syn_id' => '',
                'syn_type'=>'',
                'target_type'=>'',
                'is_target'=>'',
                'goods_id' => '',
                'num'=>'',
            ]);
            $param['is_target'] = 3;
            try {
                $count = $this->repository->search(['syn_id'=>$param['syn_id'],'goods_id'=>$param['goods_id'],'target_type'=>$param['target_type'],'is_target'=>3],$this->request->companyId)->count('id');
                if($count > 0) return $this->error('此门槛已存在!');
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
            $this->commons();
            return $this->fetch('active/syn/synInfo/addMateria');
        }
    }

    /**
     * 编辑广告
     */
    public function editSill()
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
                'syn_id' => '',
                'syn_type'=>'',
                'target_type'=>'',
                'goods_id' => '',
                'num'=>'',
            ]);
            $param['is_target'] = 3;
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
            $this->commons();
            return $this->fetch('active/syn/synInfo/addMateria', [
                'info' => $info,
            ]);
        }
    }


    /**
     * 删除广告
     */
    public function delSill()
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