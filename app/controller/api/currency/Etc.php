<?php

namespace app\controller\api\currency;

use app\common\repositories\currency\EtcRepository;
use app\controller\api\Base;

class Etc extends Base
{

    public function withdrawalConfig()
    {
//        [
//            2, 5, 10, 20, 50, 100
//        ]
        return $this->success([
            ['amount' => 2, 'tips' => '邀请1名实名新用户'],
            ['amount' => 5, 'tips' => '邀请5名实名新用户'],
            ['amount' => 10, 'tips' => '扭蛋等级达10级,再邀请5名实名新用户'],
            ['amount' => 20, 'tips' => '扭蛋等级达20级,再邀请5名实名新用户'],
            ['amount' => 50, 'tips' => '扭蛋等级达50级,再邀请5名实名新用户'],
            ['amount' => 100, 'tips' => '扭蛋等级达110级,再邀请5名实名新用户'],
        ]);
    }

    public function withdrawal(EtcRepository $repository)
    {
        $data = $this->request->param(['num' => '', 'pay_password' => '', 'hash' => '', 'type' => 1]);
        if (!$data['num'] || $data['num'] < 0) return $this->error('数量错误!');
        if (!$data['pay_password']) return $this->error('请输入交易密码!');
//        if (!$data['hash']) return $this->error('请输入hash地址!');
        return $this->success($repository->withdrawal($data, $this->request->userInfo(), $this->request->companyId));
    }

    public function getLog(EtcRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        return $this->success($repository->getApiList($page, $limit, $this->request->userInfo(), $this->request->companyId));
    }
}