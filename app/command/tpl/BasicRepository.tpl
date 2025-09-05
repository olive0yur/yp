<?php

namespace app\common\repositories{$namespace};

use app\common\dao{$namespace}\{$modelName}Dao;
use app\common\repositories\BaseRepository;

class {$modelName}Repository extends BaseRepository
{

    public function __construct({$modelName}Dao $dao)
    {
        $this->dao = $dao;
    }
}