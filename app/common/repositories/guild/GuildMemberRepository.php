<?php

namespace app\common\repositories\guild;

use app\common\dao\guild\GuildMemberDao;
use app\common\model\guild\GuildMemberModel;
use app\common\repositories\BaseRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;

/**
 * Class GuideMemberRepository
 * @package app\common\repositories\GuideMemberRepository
 * @mixin GuildMemberDao
 */
class GuildMemberRepository extends BaseRepository
{

    public function __construct(GuildMemberDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->with(['user'=>function($query){
            $query->field('id,mobile');
        },'guild'=>function($query){
            $query->field('id,guild_name,uuid');
        }])->page($page, $limit)
            ->select();
        return compact('count', 'list');
    }



    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        $data['create_time'] = date('Y-m-d H:i:s');
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

    public function delMember($data,$userInfo,$companyId){
        if(!$data['id']) throw new ValidateException('参数[id]错误!');
        /** @var GuildRepository  $guideRepository */
        $guideRepository = app()->make(GuildRepository::class);
        $guide = $guideRepository->search(['uuid'=>$userInfo['id']],$companyId)->find();
        if(!$guide) throw new ValidateException('只有会长可以进行此操作哦！');
        $info = $this->dao->search(['guild_id'=>$guide['id']],$companyId)->where(['uuid'=>$data['id']])->find();
        if(!$info) throw new ValidateException('当前用户不在您的公会哦！');
        if($info['uuid'] == $userInfo['id']) throw new ValidateException('不能删除自己哦!');
        if($info->delete()) return true;
        throw new ValidateException('网络错误，删除失败!');
    }

    public function getUserList($page,$limit,$userInfo,$companyId){
        $guid_id = $this->dao->search(['uuid'=>$userInfo['id']])->value('guild_id');
        if($guid_id){
            $query = (new GuildMemberModel)
                ->alias('me')
                ->join('users u','u.id = me.uuid')
                ->field('me.*,u.id')
                ->where(['guild_id'=>$guid_id,'u.company_id'=>$companyId])
                ->withAttr('user',function($v,$data) use($companyId){
                    $info = app()->make(UsersRepository::class)
                        ->search([],$companyId)
                        ->where(['id'=>$data['uuid']])->field('id,mobile,nickname,head_file_id,user_code')
                        ->with(['avatars'=>function($info){
                            $info->bind(['picture'=>'show_src']);
                        }])
                        ->withSum('pList','product')
                        ->withAttr('mobile',function ($vv,$ii){
                            return substr_replace($ii['mobile'],'****',3,4);
                        })->find();
                    return $info;
                });
            $query->append(['user']);
            $count = $query->count();
            $list = $query->page($page, $limit)->select();
            return compact('count', 'list');
        }
        return ['count'=>0,'list'=>[]];
    }

    public function getUserList1($page,$limit,$userInfo,$companyId){
        $guid_id = $this->dao->search(['uuid'=>$userInfo['id']])->value('guild_id');
        $query = $this->dao->search(['guild_id'=>$guid_id], $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['user'=>function($query){
                $query->field('id,mobile,nickname,head_file_id')->with(['avatars'=>function($query){
                    $query->bind(['picture'=>'show_src']);
                }])->withSum('pList','product')
                    ->withAttr('mobile',function ($v,$data){
                        return substr_replace($data['mobile'],'****',3,4);
                    })
                ;
            }])
            ->select();
        return compact('count', 'list');
    }
}