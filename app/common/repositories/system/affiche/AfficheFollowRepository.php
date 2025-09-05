<?php

namespace app\common\repositories\system\affiche;

use app\common\dao\system\affiche\AfficheFollowDao;
use app\common\repositories\BaseRepository;
use think\exception\ValidateException;


class AfficheFollowRepository extends BaseRepository
{
    public function __construct(AfficheFollowDao $dao)
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
            ->with([
                'affiche'
            ])
            ->select();
        return compact('list', 'count');
    }

    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        $data['add_time'] = date('Y-m-d H:i:s');
        return $this->dao->create($data);
    }


    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->with([
                'affiche'
            ])
            ->hidden(['file'])
            ->where('id', $id)
            ->find();

        return $data;
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

    public function getApiList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with([
                'affiche'=>function($query){
                  $query->with([
                      'file'=>function($query){
                      $query->field('id,show_src,width,height');
                  },'cateInfo'=>function($query){
                          $query->field('id,name');
                      }

                  ]);
                }
            ])
            ->select();
        return compact('list', 'count');
    }

    public function follow(array $data,int $uuid,int $companyId){

       $detail = app()->make(AfficheRepository::class)->search(['id'=>$data['id']],$companyId)->find();
       if(!$detail) throw new ValidateException('公告不存在!');
        $info = $this->dao->search(['affiche_id'=>$data['id'],'uuid'=>$uuid],$companyId)->find();
        if($info){
           api_user_log($uuid,4,$companyId,'取消关注公告:'.$detail['title']);
           $info->delete();
       }else{
           $arr['affiche_id'] = $data['id'];
           $arr['uuid'] = $uuid;
           api_user_log($uuid,4,$companyId,'关注公告:'.$detail['title']);
          return $this->addInfo($companyId,$arr);
       }
    }
}