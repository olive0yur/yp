<?php

namespace app\common\model\active\task;

use app\common\model\BaseModel;
use app\common\model\forum\ForumVoteModel;
use app\common\model\forum\VoteLogModel;
use app\common\model\pool\PoolSaleModel;
use app\common\model\system\upload\UploadFileModel;

class TaskModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'active_task';
    }



    public function pool(){
        return $this->hasOne(PoolSaleModel::class,'id','goods_id');
    }

    public function sendPool(){
        return $this->hasOne(PoolSaleModel::class,'id','send_pool_id');
    }

}
