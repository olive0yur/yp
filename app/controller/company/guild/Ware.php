<?php

namespace app\controller\company\guild;

use app\common\repositories\guild\GuildRepository;
use app\common\repositories\guild\GuildWareHouseRepository;
use app\common\repositories\pool\PoolSaleRepository;
use think\App;
use app\controller\company\Base;


class Ware extends Base
{
    protected $repository;

    public function __construct(App $app, GuildWareHouseRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;


        $this->assign([
            'sendAuth' => company_auth('companyGuildwareSend'),##
            'delAuth' => company_auth('companyGuildwareDel'),##
        ]);
    }

    public function commonParams(){
        /** @var GuildRepository $guildRepository */
        $guildRepository = $this->app->make(GuildRepository::class);
        $guildList = $guildRepository->search([],$this->request->companyId)->field('id,guild_name')->select();
        $tokens = web_config($this->request->companyId, 'site')['tokens'];

        /** @var PoolSaleRepository $poolSaleRepository */
        $poolSaleRepository = $this->app->make(PoolSaleRepository::class);
        $poolList = $poolSaleRepository->search(['is_member'=>1],$this->request->companyId)->select();
        $this->assign(['guildList'=>$guildList,'tokens'=>$tokens,'poolList'=>$poolList]);
    }

    public function list()
    {
        $this->commonParams();
        if($this->request->isAjax()) {
            $where = $this->request ->param([
                'guild_id' => '',
                'keywords'=>''
            ]);
            [$page,$limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0,'data' => $data['list'],'count' => $data['count']]);
        }
        return $this->fetch('guild/ware/list');
    }

    /**
     * 添加
     */
    public function send()
    {
        if($this->request->isPost()){
            $param = $this->request->param([
                'guild_id' => '',
                'pool_id' => '',
                'poolNum' => '',
                'num' => '',
            ]);
            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                company_user_log(4, '赠送给公会', $param);
                if($res) {
                    return $this->success('赠送成功');
                } else{
                    return $this->error('赠送失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->commonParams();
            return $this->fetch('guild/ware/send');
        }
    }

    /**
     * 删除
     */
    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                company_user_log(4, '删除仓库物品 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}