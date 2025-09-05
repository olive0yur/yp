<?php

namespace app\common\repositories\guild;

use app\common\dao\guild\GuildWareHouseDao;
use app\common\dao\guild\GuildWareLogDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class GuildWareLogRepository
 * @package app\common\repositories\GuildWareLogRepository
 * @mixin GuildWareLogDao
 */
class GuildWareLogRepository extends BaseRepository
{

    public function __construct(GuildWareLogDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->with(['user'=>function($query){
            $query->field('id,mobile');
            $query->bind(['mobile'=>'mobile']);
        },'pool'=>function($query){
            $query->bind(['title']);
        },'guild'=>function($query){
            $query->bind(['guild_name']);
        }])->page($page, $limit)
            ->select();
        return compact('count', 'list');
    }



    public function addInfo($companyId,$data)
    {
        return Db::transaction(function () use ($data, $companyId) {
            $data['company_id'] = $companyId;
            $data['create_time'] = date('Y-m-d H:i:s');
            $this->dao->create($data);
            return true;
        });

    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->where('id', $id)
            ->find();

        return $data;
    }

    /**
     * 删除
     */
    public function batchDelete(array $ids)
    {
        $list = $this->dao->selectWhere([
            ['id', 'in', $ids]
        ]);

        if ($list) {
            foreach ($list as $k => $v) {
                $this->dao->delete($v['id']);
            }
            return $list;
        }
        return [];
    }

    public function getApiList($page,$limit,$userInfo,$companyId){

        /** @var GuildRepository $guildRepository */
        $guildRepository=app()->make(GuildRepository::class);
        $guide = $guildRepository->search(['uuid'=>$userInfo['id']])->find();
        if(!$guide) throw new ValidateException('只有会长可以进行此操作!');


        $query = $this->dao->search(['guild_id'=>$guide['id']], $companyId);
        $count = $query->count();
        $list = $query->with(['user'=>function($query){
            $query->field('id,mobile');
            $query->bind(['mobile'=>'mobile']);
        },'pool'=>function($query){
            $query->bind(['title']);
        },'guild'=>function($query){
            $query->bind(['guild_name']);
        }])->page($page, $limit)
            ->select();
        return compact('count', 'list');
    }

}