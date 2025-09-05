<?php

namespace app\common\model\box;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\product\ProductRepository;
use app\common\repositories\system\upload\UploadFileRepository;

class BoxSaleGoodsListModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'box_goods_list';
    }

    public function getGoodsAttr($v,$data)
    {
        switch ($data['goods_type']){
            case 1:
               $repository = app()->make(PoolSaleRepository::class);
                $goods = $repository->search([],$data['company_id'])->where('id',$data['goods_id'])->field('id,title,file_id')->find();
                $goods['picture'] = (new UploadFileModel)->where('id',$goods['file_id'])->value('show_src');
               break;
            case 2:
                $repository = app()->make(PoolSaleRepository::class);
                $goods = $repository->search([],$data['company_id'])->where('id',$data['goods_id'])->field('id,title,file_id')->find();
                $goods['picture'] = (new UploadFileModel)->where('id',$goods['file_id'])->value('show_src');
                break;
            case 3:
               $goods['title'] = web_config($data['company_id'], 'site')['tokens'].'X'.$data['goods_id'];
               $goods['picture'] = (new UploadFileModel)->where('id',$data['file_id'])->value('show_src');;
               break;
        }
        return $goods;
    }
}
