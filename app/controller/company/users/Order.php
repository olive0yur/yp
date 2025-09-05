<?php

namespace app\controller\company\users;

use app\common\repositories\pool\PoolShopOrder;
use think\App;
use app\controller\company\Base;

class Order extends Base
{
    protected $repository;

    public function __construct(App $app, PoolShopOrder $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign([
            'editAuth' => company_auth('companyOrderStatus'),
        ]);
    }


    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count'] ]);
        } else {

            return $this->fetch('users/order/list', [
            ]);
        }
    }
    public function status()
    {
        $id = (int)$this->request->param('id');

        $info = $this->repository->getDetail($id);
        if (!$info) {
            return $this->error('信息错误');
        }

        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'wuliu_no'=>'',
                'wuliu_company'=>'',
            ]);
            if (!$this->repository->exists($id)) {
                return $this->error('数据不存在');
            }
            try {
                $param['wuliu_at'] = date('Y-m-d H:i:s');
                $param['status'] = 3;
                $res = $this->repository->update($id, $param);
                company_user_log(3, '修改订单状态 id:' . $id, $param);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }else{
            return $this->fetch('users/order/edit',['info'=>$info]);
        }
    }

}