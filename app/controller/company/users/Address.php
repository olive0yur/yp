<?php

namespace app\controller\company\users;

use app\common\repositories\users\UsersAddressRepository;
use app\common\repositories\users\UsersCertRepository;
use app\common\repositories\users\UsersGroupRepository;
use app\common\repositories\users\UsersLabelRepository;
use app\common\repositories\users\UsersPushRepository;
use think\App;
use app\controller\company\Base;
use app\common\repositories\users\UsersRepository;
use app\validate\users\UsersCertValidate;
use app\validate\users\UsersValidate;
use think\exception\ValidateException;
use think\facade\Db;

class Address extends Base
{
    protected $repository;

    public function __construct(App $app, UsersAddressRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;

    }


    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'status'=>''
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count'] ]);
        } else {

            return $this->fetch('users/address/list', [
                'companyId'=>$this->request->companyId,
            ]);
        }
    }

}