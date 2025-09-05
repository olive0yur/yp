<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersLabelDao;
use app\common\repositories\BaseRepository;

/**
 * Class UsersLabelRepository
 *
 * @mixin UsersLabelDao
 */
class UsersLabelRepository extends BaseRepository
{

    public function __construct(UsersLabelDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取所有标签
     *
     * @param int|null $companyId
     * @param $status
     * @return array|\think\Collection|\think\db\BaseQuery[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAll($companyId = 0, $status = '')
    {
        $query = $this->dao->getSearch([
            'is_show' => $status,
            'company_id' => $companyId
        ]);
        $list = $query->select();
        return $list;
    }

    public function getCascaderData($companyId = 0, $status = '')
    {
        $list = $this->getAll($companyId, $status);
        $list = convert_arr_key($list, 'id');
        return formatCascaderData($list, 'name', 0, 'pid', 0, 1);
    }

    /**
     * 查询
     */
    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('id,name,sort,is_show,add_time,edit_time')
            ->select();
        return compact('list', 'count');
    }

    public function addInfo($companyId, $data)
    {
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }


    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
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
}