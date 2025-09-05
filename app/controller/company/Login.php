<?php

namespace app\controller\company;

use app\common\repositories\company\user\CompanyUserRepository;
use app\controller\company\Base;
use think\facade\Db;
use app\validate\company\LoginValidate;
use think\App;
use think\exception\ValidateException;

class Login extends Base
{
    protected $repository;

    public function __construct(App $app, CompanyUserRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    /**
     * 登录页
     *
     * @return string
     * @throws \Exception
     */
    public function index()
    {
        return $this->fetch('login/index');
    }

    /**
     * 登陆操作
     *
     * @return \think\response\Json
     */
    public function doLogin()
    {
        if ($this->request->isPost()) {
            try {
                validate(LoginValidate::class)->scene('captchalogin')->check($this->request->param());
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }

            try {
                $companyInfo = $this->repository->login($this->request->param('username'), $this->request->param('password'));

                $this->repository->setSessionInfo($companyInfo);

                return json()->data(['code' => 0, 'msg' => '登陆成功']);
            } catch (ValidateException $e) {
                return json()->data(['code' => -1, 'msg' => $e->getError()]);
            }
        }
    }
}