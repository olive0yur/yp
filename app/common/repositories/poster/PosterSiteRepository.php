<?php

namespace app\common\repositories\poster;

use app\common\repositories\BaseRepository;
use app\common\dao\poster\PosterSiteDao;

/**
 * Class PosterSiteRepository
 * @package app\common\repositories\poster
 * @mixin PosterSiteDao
 */
class PosterSiteRepository extends BaseRepository
{

    public function __construct(PosterSiteDao $dao)
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
        return $this->dao->create($data);
    }


    public function editInfo($id,array $data)
    {
        return $this->dao->update($id,$data);
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

    public function getCascaderData($companyId = 0)
    {
        $data = $this->dao->getSearch(['company_id'=>$companyId])->select();
        return $data;
    }
}