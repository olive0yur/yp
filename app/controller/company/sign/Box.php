<?php

namespace app\controller\company\sign;

use app\common\repositories\sign\SignBoxRepository;
use think\App;
use think\facade\Cache;
use app\controller\company\Base;
use app\validate\pool\SaleValidate;

class Box extends Base
{
    protected $repository;

    public function __construct(App $app, SignBoxRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign([
            'addAuth' => company_auth('companySignAdd'),
            'editAuth' => company_auth('companySignEdit'),
            'delAuth' => company_auth('companySignDel'),
        ]);
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
        return $this->fetch('sign/box/list');
    }

    /**
     *
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'type' => '',
                'num' => '',
                'debris_id'=>'',
                'lv' => '',
            ]);
            if($param['lv'] >1) return $this->error('几率最大是1');
            if($param['lv'] <=0) return $this->error('几率必须大于0');
            try {
                $res = $this->repository->addInfo($this->request->companyId, $param);
                if ($res) {
                    company_user_log(3, '添加盲盒配置成功', $param);
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {

            return $this->fetch('sign/box/add', [
                'tokens'=>web_config($this->request->companyId,'site')['tokens']
            ]);
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
                'id' => '',
                'type' => '',
                'num' => '',
                'debris_id'=>'',
                'lv' => '',
            ]);
            if($param['lv'] >1) return $this->error('几率最大是1');
            if($param['lv'] <=0) return $this->error('几率必须大于0');
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
            return $this->fetch('sign/box/edit', [
                'info' => $info
            ]);
        }
    }


    /**
     * 设置卡牌状态
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
                admin_log(3, '修改卡牌状态 id:' . $id, $param);
                if ($res) {
                    Cache::store('redis')->delete('goods_' . $id);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
    }


    public function market()
    {
        $id = (int)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'is_mark' => '',
            ]);

            if (!$this->repository->exists($id)) {
                return $this->error('数据不存在');
            }
            try {
                $res = $this->repository->update($id, $param);
                if ($param['is_mark'] == 2) {
                    $push['id'] = $param['id'];
                    $push['company_id'] = $this->request->companyId;
                    ##后台在关闭某个卡牌的市场寄售时， 场中的寄售挂单需要自动取消寄售，自动下架。
                    $isPushed = \think\facade\Queue::push(\app\jobs\EndPoolMarkJob::class, $push);
                }
                admin_log(3, '修改卡牌市场开关状态 id:' . $id, $param);
                if ($res) {
                    Cache::store('redis')->delete('goods_' . $id);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
    }


    public function give()
    {
        $id = (int)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'is_give' => '',
            ]);

            if (!$this->repository->exists($id)) {
                return $this->error('数据不存在');
            }
            try {
                $res = $this->repository->update($id, $param);
                admin_log(3, '修改卡牌转赠开关状态 id:' . $id, $param);
                if ($res) {
                    Cache::store('redis')->delete('goods_' . $id);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
    }


    public function hot()
    {
        $id = (int)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'is_hot' => '',
            ]);

            if (!$this->repository->exists($id)) {
                return $this->error('数据不存在');
            }
            try {
                $res = $this->repository->update($id, $param);
                if ($res) {
                    Cache::store('redis')->delete('goods_' . $id);
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
     * 删除
     */
    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                admin_log(4, '删除卡牌 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}