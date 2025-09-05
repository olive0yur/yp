<?php

namespace app\controller\agent;

use app\common\repositories\users\user\AgentUserRepository;
use app\validate\users\LoginValidate;
use think\App;
use think\exception\ValidateException;

class Login extends Base
{
    protected $repository;
    public function __construct(App $app, AgentUserRepository $repository)
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
                validate(LoginValidate::class)->scene('captchaLogin')->check($this->request->param());
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            try {
                $companyInfo = $this->repository->login($this->request->param('mobile'), $this->request->param('password'),$this->request->param('company_code'));
                $this->repository->setSessionInfo($companyInfo);

                return json()->data(['code' => 0, 'msg' => '登陆成功']);
            } catch (ValidateException $e) {
                return json()->data(['code' => -1, 'msg' => $e->getError()]);
            }
        }
    }
}