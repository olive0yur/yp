<?php

namespace app\common\repositories\mine;

use app\common\dao\mine\DragonCannelDao;
use app\common\repositories\BaseRepository;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class MineRepository
 * @package app\common\repositories\MineRepository
 * @mixin DragonCannelDao
 */
class DragonCannelRepository extends BaseRepository
{

    public function __construct(DragonCannelDao $dao)
    {
        $this->dao = $dao;
    }


    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->with(['user'=>function($query){
            $query->field('id,nickname,mobile');
        }])->page($page, $limit)
            ->order('id desc')
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
}