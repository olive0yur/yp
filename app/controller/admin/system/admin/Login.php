<?php

namespace app\controller\admin\system\admin;

use app\common\repositories\system\admin\AdminUserRepository;
use app\controller\admin\Base;
use app\validate\admin\LoginValidate;
use think\App;
use think\exception\ValidateException;

class Login extends Base
{
    protected $repository;

    public function __construct(App $app, AdminUserRepository $repository)
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
        if ($this->request->isLogin) {
            return redirect(url('adminIndex'));
        }
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
                $adminInfo = $this->repository->login($this->request->param('username'), $this->request->param('password'));

                $this->repository->setSessionInfo($adminInfo);

                return json()->data(['code' => 0, 'msg' => '登陆成功']);
            } catch (ValidateException $e) {
                return json()->data(['code' => -1, 'msg' => $e->getError()]);
            }
        }
    }
}