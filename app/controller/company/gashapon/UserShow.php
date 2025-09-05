<?php

namespace app\controller\company\gashapon;

use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\gashapon\GashaponRepository;
use app\common\repositories\gashapon\GashaponUserRepository;
use think\App;
use app\controller\company\Base;

class UserShow extends Base
{
    protected $repository;

    public function __construct(App $app, GashaponUserRepository $repository)
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
        return $this->fetch('gashapon/usershow/list', [
            'addAuth' => company_auth('companyGashaponUserAdd'),
            'editAuth' => company_auth('companyGashaponUserEdit'),
            'delAuth' => company_auth('companyGashaponUserDel'),##
        ]);
    }

    /**
     * 空投用户
     * @return string|\think\response\Json|\think\response\View
     * @throws \Exception
     */
    public function giveUser()
    {
        $id = (array)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'num' => '',
                'remark' => ''
            ]);
            if ($param['num'] <= 0) {
                return $this->error('空投数量必须大于0');
            }
            if ($param['num'] >= 500) {
                return $this->error('空投数量过大');
            }
            $data = $this->repository->batchGiveUser($id,$param);
            company_user_log(3, '批量扭蛋机 id:' . implode(',', $id), $param);
            return $this->success('投送成功');
        } else {
            /**
             * @var BoxSaleRepository $boxSaleRepository
             */
            $boxSaleRepository = app()->make(BoxSaleRepository::class);
            $boxData = $boxSaleRepository->getCascaderData($this->request->companyId);
            return $this->fetch('gashapon/usershow/give', [
                'boxData' => $boxData,
            ]);
        }
    }
}