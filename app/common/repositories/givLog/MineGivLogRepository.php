<?php

namespace app\common\repositories\givLog;

use app\common\dao\givLog\GivLogDao;
use app\common\dao\givLog\MineGivLogDao;
use app\common\repositories\BaseRepository;

/**
 * Class MineGivLogRepository
 * @package app\common\repositories\MineGivLogRepository
 * @mixin MineGivLogDao
 */
class MineGivLogRepository extends BaseRepository
{

    public function __construct(MineGivLogDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->select();
        return compact('count', 'list');
    }



    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        $data['add_time'] = date('Y-m-d H:i:s');
        return $this->dao->create($data);
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


    public function getApiList(array $where, $page, $limit,$uuid, $companyId = null)
    {
        $where['uuid'] = $uuid;
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->with(['user'=>function($query){
            $query->field('id,user_code,nickname,head_file_id')->with([
                'avatars' => function ($query) {
                    $query->bind(['avatar' => 'show_src']);
                }
            ]);
        }])->page($page, $limit)
            ->order('id desc')
            ->select();
        return compact('count', 'list');
    }
}