<?php

namespace app\common\dao{$namespace};

use app\common\dao\BaseDao;
use app\common\model{$namespace}\{$modelName}Model;

class {$modelName}Dao extends BaseDao
{

    /**
     * @return {$modelName}Model
     */
    protected function getModel(): string
    {
        return {$modelName}Model::class;
    }

}
