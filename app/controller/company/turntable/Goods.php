<?php

namespace app\controller\company\turntable;

use app\common\repositories\game\RoleRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\turntable\TurntableGoodsRepository;
use think\App;
use app\controller\company\Base;

class Goods extends Base
{
    protected $repository;

    public function __construct(App $app, TurntableGoodsRepository $repository)
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
        $tokens = web_config($this->request->companyId, 'site')['tokens'];
        return $this->fetch('turntable/goods/list', [
            'addAuth' => company_auth('companyRoleListAdd'),
            'editAuth' => company_auth('companyRoleListEdit'),
            'delAuth' => company_auth('companyTurntableListDel'),##
            'tokens'=>$tokens
        ]);
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'type' => '',
                'debris_id' => '',
                'lv' => '',
                'per' => '',
                'pool_id' => '',
            ]);
            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                if ($res) {
                    company_user_log(3, '转盘物品添加', $param);
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误'.$e->getMessage());
            }
        } else {
            $tokens = web_config($this->request->companyId, 'site')['tokens'];
            $this->assign('tokens',$tokens);

            /** @var PoolSaleRepository $poolSaleRepository */
            $poolSaleRepository = app()->make(PoolSaleRepository::class);
            $poolList = $poolSaleRepository->search([],$this->request->companyId)->where('stock','>=',0)->field('id,title')->select();
            $this->assign('poolList',$poolList);
            return $this->fetch('turntable/goods/add');
        }
    }


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
                'id' => '',
                'type' => '',
                'debris_id' => '',
                'lv' => '',
                'per' => '',
                'pool_id' => '',
            ]);
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res !== false) {
                    company_user_log(3, '转盘物品修改', $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $tokens = web_config($this->request->companyId, 'site')['tokens'];
            $this->assign('tokens',$tokens);
            /** @var PoolSaleRepository $poolSaleRepository */
            $poolSaleRepository = app()->make(PoolSaleRepository::class);
            $poolList = $poolSaleRepository->search([],$this->request->companyId)->where('stock','>=',0)->field('id,title')->select();
            $this->assign('poolList',$poolList);
            return $this->fetch('turntable/goods/edit', [
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