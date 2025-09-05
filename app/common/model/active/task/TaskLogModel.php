<?php

namespace app\common\model\active\task;

use app\common\model\BaseModel;
use app\common\model\forum\ForumVoteModel;
use app\common\model\forum\VoteLogModel;
use app\common\model\pool\PoolSaleModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersModel;

class TaskLogModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'active_task_log';
    }



    public function task(){
        return $this->hasOne(TaskModel::class,'id','task_id');
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }
}
