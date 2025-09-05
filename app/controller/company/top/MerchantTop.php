<?php

namespace app\controller\company\top;

use app\common\repositories\agent\AgentRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\guild\GuildRepository;
use app\common\repositories\guild\GuildWareHouseRepository;
use app\common\repositories\guild\GuildWareLogRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\top\UsersTopRepository;
use app\common\repositories\users\UsersCertRepository;
use app\common\repositories\users\UsersGroupRepository;
use app\common\repositories\users\UsersLabelRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
use think\App;
use app\controller\company\Base;
use app\common\repositories\users\UsersRepository;
use app\validate\users\UsersCertValidate;
use app\validate\users\UsersValidate;
use think\exception\ValidateException;
use think\facade\Db;

class MerchantTop extends Base
{
    protected $repository;

    public function __construct(App $app, UsersTopRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign([
            'delAuth' => company_auth('companyMerchantInfoDel'),
        ]);
    }


    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'top_id' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count'] ]);
        } else {
            $top_id = $this->request->get('top_id');
            $this->assign('top_id',$top_id);
            return $this->fetch('top/merchant/info');
        }
    }



    /**
     * 删除用户
     */
    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                admin_log(4, '删除卡牌 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}