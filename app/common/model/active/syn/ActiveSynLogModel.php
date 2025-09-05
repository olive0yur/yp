<?php

namespace app\common\model\active\syn;

use app\common\model\active\ActiveModel;
use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersModel;
use app\common\model\users\UsersPoolModel;
use app\common\repositories\users\UsersPoolRepository;

class ActiveSynLogModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'active_syn_log';
    }


    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }

    public function synInfo(){
        return $this->hasOne(ActiveSynInfoModel::class,'id','syn_info_id');
    }

    public function cover(){
        return $this->hasOne(UploadFileModel::class,'id','file_id');
    }


    public function getGoodsAttr($v,$data){
         $ids = explode(',',$data['goodsIds']);
         $usersPoolRepository =  app()->make(UsersPoolRepository::class);
         return $usersPoolRepository->search([],$data['company_id'])->field('id,pool_id,no')->whereIn('id',$ids)
              ->with(['pool'=>function($query){
                  $query->field('id,title,file_id')->with(['cover'=>function($query){
                      $query->field('id,show_src,width,height');
                  }]);
              }])
             ->select();
    }

    public function getGoodsNameAttr($v,$data){
        $ids = explode(',',$data['goodsIds']);
        $usersPoolRepository =  app()->make(UsersPoolRepository::class);
        $list = $usersPoolRepository->search([],$data['company_id'])->field('id,pool_id,no')->whereIn('id',$ids)
            ->with(['pool'=>function($query){
                $query->bind(['title']);
            }])->select();
        $names = array_column(json_decode($list,true),'title');
        return implode(',',$names);
    }

    public function no(){
        return $this->hasOne(UsersPoolModel::class,'id','syn_pool_id');
    }


}
