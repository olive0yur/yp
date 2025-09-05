<?php

namespace app\controller\admin\system;

use app\common\repositories\system\ConfigRepository;
use app\common\services\UploadService;
use app\controller\admin\Base;
use think\App;

class Website extends Base
{
    protected $repository;

    public function __construct(App $app, ConfigRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    /**
     * 网站设置
     *
     * @return string|\think\response\Json
     * @throws \Exception
     */
    public function siteInfo()
    {
        if ($this->request->isPost()) {
            $this->repository->modifyConfig('site', $this->request->post(), 0);
            admin_log(5, '设置网站信息', $this->request->post());
            return json()->data(['code' => 0,'msg' => '修改成功']);
        } else {
            return $this->fetch('system/website/site_info', [
                'info' => $this->repository->getConfig(0,'site')
            ]);
        }
    }

    /**
     * 应用设置
     *
     * @return string|\think\response\Json
     * @throws \Exception
     */
    public function programInfo()
    {
        if ($this->request->isPost()) {
            $this->repository->modifyConfig('program', $this->request->post(),0);
            admin_log(5, '设置应用配置', $this->request->post());
            return json()->data([ 'code' => 0,'msg' => '修改成功']);
        } else {
            return $this->fetch('system/website/program_info', [
                'info' => web_config(0,'program')
            ]);
        }
    }
}