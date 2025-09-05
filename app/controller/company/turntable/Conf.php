<?php

namespace app\controller\company\turntable;

use app\common\repositories\system\ConfigRepository;
use app\controller\company\Base;
use think\App;

class Conf extends Base
{
    protected $repository;

    public function __construct(App $app, ConfigRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    /**
     * 应用设置
     *
     * @return string|\think\response\Json
     * @throws \Exception
     */
    public function conf()
    {
        if ($this->request->isPost()) {
            $this->repository->modifyConfig('turn', $this->request->post(), $this->request->companyId);
            company_user_log(2, '转盘配置', $this->request->post());
            return json()->data([ 'code' => 0, 'msg' => '修改成功']);
        } else {
            $info = web_config($this->request->companyId, 'turn');
            return $this->fetch('turntable/conf/conf', [
                'info' =>$info,
                'companyId' => $this->request->companyId,
            ]);
        }
    }


}