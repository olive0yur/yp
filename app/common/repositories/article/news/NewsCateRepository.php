<?php

namespace app\common\repositories\article\news;

use think\facade\Db;
use app\common\repositories\BaseRepository;
use app\common\dao\article\news\NewsCateDao;

/**
 * Class NewsCateRepository
 * @package app\common\repositories\article\news
 * @mixin NewsCateDao
 */
class NewsCateRepository extends BaseRepository
{

    public function __construct(NewsCateDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('id,name,keywords,desc,sort,add_time,sort,is_show')
            ->order('sort asc')
            ->select();
        return compact('count', 'list');
    }


    public function addInfo($companyId, $data)
    {
        return Db::transaction(function () use ($data, $companyId) {
            $data['company_id'] = $companyId;
            $info = $this->dao->create($data);
            return $info;
        });
    }

    public function editInfo($id, array $data)
    {
        return Db::transaction(function () use ($data, $id) {
            $res= $this->dao->update($id,$data);
            return ($res);
        });
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


    /**
     * 获取所有组
     * @param int|null $companyId
     * @param $status
     * @return array|\think\Collection|\think\db\BaseQuery[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAll(int $companyId = null, $isShow = 1)
    {
        $query = $this->dao->search([
            'is_show' => $isShow
        ], $companyId);
        $list = $query->select();
        return $list;
    }

    public function getCateData(int $companyId = null, $isShow = '')
    {
        $list = $this->dao->search([
            'is_show' => $isShow
        ], $companyId)->column('name', 'id');
        return formatCascaderData($list, 'name');
    }

}