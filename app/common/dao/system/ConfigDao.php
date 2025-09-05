<?php

namespace app\common\dao\system;

use app\common\dao\BaseDao;
use app\common\model\system\ConfigModel;

class ConfigDao extends BaseDao
{

    /**
     * @return ConfigModel
     */
    protected function getModel(): string
    {
        return ConfigModel::class;
    }

}
