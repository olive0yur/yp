<?php

namespace app\controller\company\system;

use app\common\repositories\system\PaymentRepository;
use app\common\services\PaymentService;
use app\controller\company\Base;
use app\validate\system\PaymentValidate;
use think\App;

class Payment extends Base
{
    protected $types = PaymentService::TYPES;

    protected $repository;

    public function __construct(App $app, PaymentRepository $repository)
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
        return $this->fetch('system/payment/list', [
            'addAuth' => company_auth('companyPaymentAdd'),
            'editAuth' => company_auth('companyPaymentEdit'),
            'delAuth' => company_auth('companyPaymentDel'),
            'switchStatusAuth' => company_auth('companyPaymentSwitch'),##上架/下架
        ]);
    }

    public function paymentType()
    {
        $this->payments = [];
        foreach ($this->types as $k => $vo) {
            $allow = [];
            $this->payments[$k] = array_merge($vo, ['allow' => join('、', $allow)]);
        }
        $this->assign('payments', $this->payments);
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'type' => '',
                'name' => '',
                'cover' => '',
                'content' => '',
                'is_fee' => '',
            ]);
            validate(PaymentValidate::class)->scene('add')->check($param);
            if ($this->repository->companyFieldExists($this->request->companyId, 'type', $param['type'])) {
                return $this->error(sprintf('支付方式【%s】已配置，无法重复配置', $this->types[$param['type']]['name'] ?? ''));
            }
            $param['content'] = json_encode($this->request->post() ?: [], JSON_UNESCAPED_UNICODE);
            try {
                $res = $this->repository->addInfo($this->request->companyId, $param);
                if ($res) {
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->paymentType();
            return $this->fetch('system/payment/add');
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
                'name' => '',
                'cover' => '',
                'content' => '',
                'sort' => '',
                'is_fee' => '',
            ]);
            validate(PaymentValidate::class)->scene('edit')->check($param);
            if ($this->repository->companyFieldExists($this->request->companyId, 'type', $param['type'], $info['id'])) {
                return $this->error(sprintf('支付方式【%s】已配置，无法重复配置', $this->types[$param['type']]['name'] ?? ''));
            }
            $param['content'] = json_encode($this->request->post() ?: [], JSON_UNESCAPED_UNICODE);
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res !== false) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->paymentType();
            return $this->fetch('system/payment/edit', [
                'info' => $info,

            ]);
        }
    }


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
                admin_log(3, '修改支付状态 id:' . $id, $param);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
    }

    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                admin_log(4, '删除支付方式 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}