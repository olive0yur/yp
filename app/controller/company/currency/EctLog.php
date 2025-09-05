<?php

namespace app\controller\company\currency;

use app\common\repositories\currency\EtcRepository;
use app\common\repositories\users\UsersRepository;
use think\App;
use app\controller\company\Base;


class EctLog extends Base
{
    protected $repository;

    public function __construct(App $app, EtcRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign([
            'statusAuth' => company_auth('companyCurrencyStatus'),##
        ]);
    }

    public function list()
    {

        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => ''
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count']]);
        }
        return $this->fetch('currency/etc/list');
    }

    /**
     * 审核
     */

    public function status()
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
                'status' => '',
                'remark' => '',
            ]);

            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res) {
                    if ($param['status'] == 3) {
                        /** @var UsersRepository $usersRepository */
                        $usersRepository = app()->make(UsersRepository::class);
                        $usersRepository->batchBalanceChange($info['uuid'], 4, $info['num'], ['remark' => '提现驳回'], 1);
                    }
                    company_user_log(4, ' 审核提现id:' . $info['id'], $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('currency/etc/edit', [
                'info' => $info,
            ]);
        }
    }
}