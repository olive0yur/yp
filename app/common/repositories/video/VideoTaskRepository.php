<?php

namespace app\common\repositories\video;

use app\common\dao\guild\GuildDao;
use app\common\dao\video\VideoTaskDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Log;

/**
 * Class GuideRepository
 * @package app\common\repositories\GuideRepository
 * @mixin GuildDao
 */
class VideoTaskRepository extends BaseRepository
{

    public function __construct(VideoTaskDao $dao)
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


    public function getApiList($page,$limit,$userInfo,$companyId)
    {
        $query = $this->dao->search([], $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->where('id > 0')
            ->withAttr('use_num',function ($v,$data) use($userInfo,$companyId){
                return app()->make(UserTaskRepository::class)->search([
                    'uuid'=>$userInfo['id'],'task_id'=>$data['id']
                ],$companyId)->whereTime('add_time', 'today')->count('id');
            })
            ->append(['use_num'])
            ->order('id desc')
            ->select();
        return compact('count', 'list');
    }

    private $key = 'f025f6f1e66b0c50911835b2a9a0bc57bf2fab971815bb97be5135695d6a9582';
    public function setCallback($data){
        Log::info('广告播放：' . var_export($data, true));
        if (!isset($data['sign']) && empty($data['sign'])) return false;
        $result = hash('sha256', $this->key . ':' . $data['trans_id']);
        if ($result != $data['sign']) false;
        $info = json_decode($data['extra'], true);
        $this->taskObtain(['uuid' => $info['uuid'], 'type' => $info['id']]);
        return true;
    }

    ##任务回调
    public function taskObtain($data) {
        if (isset($data['uuid']) && isset($data['type'])) {
            $user = DataUser::mk()->where('id', $data['uuid'])->find();
            if (!$user) return true;
            $type = $data['type'];
            $info = DataTask::mk()->where('id', $type)->find();
            if (!$info) return true;
            $count = DataTaskLog::mk()->where(['type' => $type, 'uuid' => $user['id'], 'level' => 0])
                ->whereTime('create_at', 'today')
                ->count('id');
            if ($info['num'] > $count) {
                DataTask::mk()->addInfo($user['id'], $info['id'], $info['amount']);
            }
        }
        return true;
    }

    public function getApiDetail($userInfo,$companyId)
    {
        $data = $this->dao->search(['uuid'=>$userInfo['id']],$companyId)
            ->find();
        return $data;
    }


}