<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersTrackDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\company\user\CompanyUserRepository;

class UsersTrackRepository extends BaseRepository
{

    public function __construct(UsersTrackDao $dao)
    {
        $this->dao = $dao;
    }


    /**
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     */
    public function getList(array $where, $page, $limit, int $companyId = 0)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with([
                'userInfo' => function ($query) {
                    $query->field('id,mobile');
                    $query->bind(['mobile' => 'mobile']);
                }
            ])
            ->field('id,user_id,track_ip,track_type,track_port,remark,add_time')
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }


    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        $data['add_time'] = date('Y-m-d H:i:s');
        return $this->dao->create($data);
    }



    /**
     * 添加日志
     *
     * @param int $logType 日志类型
     * @param string $note 日志信息
     * @param array $otherData 日志操作数据
     * @param int $adminId 管理员id
     * @param int $companyId 企业id
     * @param string $token 操作token
     * @return \app\common\dao\BaseDao|\think\Model
     */
    public function addLog($user_id, $track_type,$companyId, $remark = '', $track_port = 4)
    {
        $data = [
            'company_id' => $companyId,
            'user_id' => $user_id,
            'track_ip' => app('request')->ip(),
            'track_type' => $track_type,
            'track_port' => $track_port,
            'remark' => $remark,
            'add_time' => date('Y-m-d H:i:s'),
        ];
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

}