<?php

namespace app\common\model\union;

use app\common\model\BaseModel;
use app\common\model\pool\PoolSaleModel;
use app\common\model\box\BoxSaleModel;
use app\common\model\product\ProductModel;
use app\common\model\system\upload\UploadFileModel;

class UnionConvertModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'union_convert';
    }

    public function getSourceOutInfoAttr($v,$on)
    {
        $data = [];
        switch ($on['buy_type']){
            case 1:
                $data = PoolSaleModel::where('id', $this->out_id)
                    ->field('id,title,file_id')
                    ->find();
                $data['cover'] = (new UploadFileModel())->where('id',$data['file_id'])->find();
                break;
            case 2:
                $data = BoxSaleModel::where('id', $this->out_id)
                    ->field('id,title,file_id')
                    ->find();
                $data['cover'] = (new UploadFileModel())->where('id',$data['file_id'])->find();
                break;
            case 3:
                $data = ProductModel::where('id', $this->out_id)
                    ->field('id,title')
                    ->find();
                break;
        }
        return $data;
    }

    public function getSourceAddInfoAttr($v,$on)
    {
        $data = [];
        switch ($on['buy_type']){
            case 1:
                $data = PoolSaleModel::where('id', $this->add_id)
                    ->field('id,title,file_id')
                    ->find();
                $data['cover'] = (new UploadFileModel())->where('id',$data['file_id'])->find();
                break;
            case 2:
                $data = BoxSaleModel::where('id', $this->add_id)
                    ->field('id,title,file_id')
                    ->find();
                $data['cover'] = (new UploadFileModel())->where('id',$data['file_id'])->find();
                break;
            case 3:
                $data = ProductModel::where('id', $this->add_id)
                    ->field('id,title')
                    ->find();
                break;
        }
        return $data;
    }
    public function getCoverAttr($v,$on){
        return (new UploadFileModel())->where('id',$on['file_id'])->find();
    }
}
