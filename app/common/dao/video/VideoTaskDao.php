<?php

namespace app\common\dao\video;

use app\common\dao\BaseDao;
use app\common\model\guild\GuildModel;
use app\common\model\video\VideoTaskModel;
use think\db\BaseQuery;

class VideoTaskDao extends BaseDao
{

    /**
     * @param array $where
     * @return BaseQuery
     */
    public function search(array $where,int $companyId=null)
    {
        $query = VideoTaskModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['name']) && $where['name'] !== '', function ($query) use ($where) {
                $query->whereLike('name', "%".$where['name']."%");
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->whereLike('name', "%".$where['keywords']."%");
            });;

        return $query;
    }

    /**
     * @return GuildModel
     */
    protected function getModel(): string
    {
        return VideoTaskModel::class;
    }



}
