<?php

namespace app\common\model\pool;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersModel;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\pool\PoolModeRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersMarkRepository;

class ShopOrderListModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pool_shop_order_list';
    }

}
