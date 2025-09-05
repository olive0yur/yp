<?php

namespace app\common\repositories\video;

use app\common\dao\guild\GuildDao;
use app\common\dao\video\UserTaskDao;
use app\common\dao\video\VideoTaskDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * Class GuideRepository
 * @package app\common\repositories\GuideRepository
 * @mixin GuildDao
 */
class UserTaskRepository extends BaseRepository
{

    public function __construct(UserTaskDao $dao)
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
        }])->page($page, $limit)
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

    public function getApiDetail($userInfo,$companyId)
    {
        $data = $this->dao->search(['uuid'=>$userInfo['id']],$companyId)
            ->find();
        return $data;
    }


}