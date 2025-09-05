<?php

namespace app\common\repositories\system\upload;

use app\common\dao\system\upload\UploadFileGroupDao;
use app\common\repositories\BaseRepository;
use app\traits\CategoryRepository;

/**
 * Class UploadFileGroupRepository
 *
 * @package app\common\repositories\system\upload
 * @mixin UploadFileGroupDao
 */
class UploadFileGroupRepository extends BaseRepository
{
    use CategoryRepository;

    public function __construct(UploadFileGroupDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取分组列表
     *
     * @return array|\think\Collection|\think\db\BaseQuery[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($companyId = null)
    {
        $query = $this->dao->search($companyId);
        $list = $query->field('id,group_name,pid')->select();
        return $list;
    }

    public function editInfo($id,$data){
        $this->dao->update($id,$data);
    }

    public function getCascadesData(int $companyId = null)
    {
        $list = $this->getList($companyId);
        $list = convert_arr_key($list->toArray(), 'id');
        return formatCascaderData($list, 'group_name');
    }

}