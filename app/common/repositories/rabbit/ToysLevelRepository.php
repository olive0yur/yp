<?php

namespace app\common\repositories\rabbit;

use app\common\dao\rabbit\ToysLevelDao;
use app\common\dao\sign\SignSetDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\sign\SignAbcRepository;
use app\common\repositories\sign\SignRepository;
use think\exception\ValidateException;

/**
 * Class ToysLevelRepository
 * @package app\common\repositories\rabbit
 * @mixin ToysLevelDao
 */
class ToysLevelRepository extends BaseRepository
{

    public function __construct(ToysLevelDao $dao)
    {
        $this->dao = $dao;

    }
    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
                 ->order('lv asc')
                 ->select();
        return compact('count', 'list');
    }



    public function editInfo($info, $data)
    {

        return $this->dao->update($info['id'],$data);
    }

    public function addInfo($companyId,$data)
    {

        $data['company_id'] = $companyId;
        $data['create_at'] = date('Y-m-d H:i:s');
        return $this->dao->create($data);
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


    public function getApiList(array $where, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query
            ->order('lv asc')
            ->select();
        return compact('count', 'list');
    }

}