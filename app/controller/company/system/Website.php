<?php

namespace app\controller\company\system;

use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\system\ConfigRepository;
use app\controller\company\Base;
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
            $this->repository->modifyConfig('site', $this->request->post(), $this->request->companyId);
            company_user_log(2, '设置网站信息', $this->request->post());
            return json()->data([ 'code' => 0, 'msg' => '修改成功']);
        } else {
            return $this->fetch('system/website/site_info', [
                'info' => web_config($this->request->companyId, 'site'),
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
            $this->repository->modifyConfig('program', $this->request->post(), $this->request->companyId);
            company_user_log(2, '设置应用配置', $this->request->post());
            return json()->data([ 'code' => 0, 'msg' => '修改成功']);
        } else {
            $info = web_config($this->request->companyId, 'program');
            $imgs = isset($info['mine']['pool']['imgs']) &&  $info['mine']['pool']['imgs']? explode(',',$info['mine']['pool']['imgs']):[];
            /** @var MineRepository $mineRepository */
            $mineRepository =  $this->app->make(MineRepository::class);
            $list = $mineRepository->search([],$this->request->companyId)->select();
            return $this->fetch('system/website/program_info', [
                'info' =>$info,
                'imgs' =>$imgs,
                'productAuth' => company_auth('companyProductList'),
                'cardPackAuth' => company_auth('companyCardPackList'),
                'forumAuth' => company_auth('companyForumList'),
                'cochain' => config('cochain.default'),
                'cardAuth' => company_auth('companyCardPackList'),
                'companyId' => $this->request->companyId,
                'list' => $list,
            ]);
        }
    }


    /**
     * 注册参数
     * @return string|\think\response\Json
     * @throws \Exception
     */
    public function registerInfo()
    {
        if ($this->request->isPost()) {
            $this->repository->modifyConfig('reg', $this->request->post(), $this->request->companyId);
            company_user_log(2, '设置应用配置', $this->request->post());
            return json()->data([ 'code' => 0, 'msg' => '修改成功']);
        } else {
            $info = web_config($this->request->companyId, 'reg');
            return $this->fetch('system/website/reg_info', [
                'info' =>$info,
            ]);
        }
    }

}