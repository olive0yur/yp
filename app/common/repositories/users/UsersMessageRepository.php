<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersMessageDao;
use app\common\repositories\system\upload\UploadFileRepository;
use think\facade\Db;
use app\common\repositories\BaseRepository;

class UsersMessageRepository extends BaseRepository
{

    public function __construct(UsersMessageDao $dao)
    {
        $this->dao = $dao;
    }
    public function getList(array $where, $page, $limit, int $companyId = null)
    {
        $query = $this->dao->search($where,$companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['userInfo'=>function($query){
                $query->field('id,mobile,nickname');
                $query->bind(['mobile','nickname']);
            }])
            ->order('id', 'desc')
            ->select()->toArray();
        $rep = app()->make(UploadFileRepository::class);
        foreach($list as $k => $v) {
            $list[$k]['files'] = array_filter(explode(',', $v['files']));
            foreach ($list[$k]['files'] as $key => $file) {
                $list[$k]['files'][$key] = $rep->search([],$companyId)->where('id',$file)->value('show_src');
            }
            $list[$k]['files'] = array_values($list[$k]['files']);
        }
        return compact('list', 'count');
    }

    public function addInfo(int $companyId = null,array $data = [])
    {
        return Db::transaction(function () use ($data,$companyId) {
            if($companyId) $data['company_id'] = $companyId;
            $data['add_time'] = date('Y-m-d H:i:s');
            $userInfo = $this->dao->create($data);
            return $userInfo;
        });
    }
    public function editInfo($info, $data)
    {
        $data['replay_time'] = date('Y-m-d H:i:s');
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id,$companyId = null)
    {

        $data = $this->dao->search([],$companyId)
            ->where('id', $id)
            ->find();
        return $data;
    }

    public function push(array $data,int $uuid,int $company_id){
        $data['uuid'] = $uuid;
//        $data['files'] = implode(',', $data['files']);
        return $this->addInfo($company_id,$data);
    }

    public function getApiList(array $where, $page, $limit, int $companyId = null)
    {
        $query = $this->dao->search($where,$companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['userInfo'=>function($query){
                $query->field('id,mobile');
                $query->bind(['mobile']);
            }])
            ->order('id', 'desc')
            ->field('id,uuid,content,title,files,is_type,replay_time,replay_text,replay_type,add_time')
            ->select();
        foreach($list as $k => $v) {
            $list[$k]['status'] = $v['status']  == 1 ? '待处理' : '已回复';
            $list[$k]['files'] = array_filter(explode(',', $v['files']));
        }
        return compact('list', 'count');
    }
}