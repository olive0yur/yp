<?php

namespace app\common\repositories\system\affiche;

use think\exception\ValidateException;
use app\common\repositories\BaseRepository;
use app\common\dao\system\affiche\AfficheCateDao;

class AfficheCateRepository extends BaseRepository
{
    public function __construct(AfficheCateDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表
     */
    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('id,name,keywords,desc,is_show,sort,add_time')
            ->order('sort desc')
            ->select();
        return compact('list', 'count');
    }


    public function addInfo($companyId, $data)
    {
        $data['company_id'] = $companyId;
        $data['add_time'] = date('Y-m-d');
        $data['edit_time'] = date('Y-m-d');
        return $this->dao->create($data);
    }


    public function editInfo($id, array $data)
    {
        return $this->dao->update($id, $data);
    }


    /**
     * 删除
     */
    public function delete(array $ids)
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
        ], $companyId)->order('sort' ,'desc');
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