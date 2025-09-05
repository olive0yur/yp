<?php

namespace app\common\repositories\poster;

use app\common\repositories\BaseRepository;
use app\common\dao\poster\PosterRecordDao;
use app\common\repositories\system\upload\UploadFileRepository;

/**
 * Class PosterRecordRepository
 * @package app\common\repositories\poster
 * @mixin PosterRecordDao
 */
class PosterRecordRepository extends BaseRepository
{

    public function __construct(PosterRecordDao $dao)
    {
        $this->dao = $dao;
    }

    public function getIsType(int $id = null)
    {
        $data = [
            1 => '首页轮播',
            2 => '广告位1',
            3 => '广告位2',
        ];
        if ($id) {
            return $data[$id] ?? '';
        }
        return $data;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $query->with([
            'file' => function ($query) {
                $query->bind(['ad_picture' => 'show_src']);
            }
        ]);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->hidden(['site', 'file'])
            ->select()->each(function ($item) {
                $item['site_name'] = $this->getIsType($item['site_id']);
            });
        return compact('count', 'list');
    }

    public function addInfo($companyId,$data)
    {
        /** @var UploadFileRepository $uploadFileRepository */
        $uploadFileRepository = app()->make(UploadFileRepository::class);
        $fileInfo = $uploadFileRepository->getFileData($data['ad_picture'], 1,0);
        if($fileInfo){
            if($fileInfo['id'] > 0){
                $data['file_id'] = $fileInfo['id'];
            }
        }
        unset($data['ad_picture']);
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }

    public function editInfo($info, $data)
    {
        /** @var UploadFileRepository $uploadFileRepository */
        $uploadFileRepository = app()->make(UploadFileRepository::class);
        $fileInfo = $uploadFileRepository->getFileData($data['ad_picture'], 1,0);
        if($fileInfo){
            if($fileInfo['id'] != $info['id']){
                $data['file_id'] = $fileInfo['id'];
            }
        }
        unset($data['ad_picture']);
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->with([
                'file' => function ($query) {
                    $query->bind(['ad_picture' => 'show_src']);
                }
            ])
            ->hidden(['file'])
            ->where('id', $id)
            ->find();

        return $data;
    }

    /**
     * 删除
     */
    public function batchDelete(array $ids)
    {
        $list = $this->dao->selectWhere([
            ['id', 'in', $ids]
        ]);
        
        if ($list) {
            foreach ($list as $k => $v) {
                $this->dao->delete($v['id']);
            }
            return $list;
        }
        return [];
    }

    public function getWebList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId)
            ->whereTime('start_time','<=',date('Y-m-d H:i:s'))
            ->whereTime('expire_time','>=',date('Y-m-d H:i:s'))
            ->field('id,site_id,file_id,open_url,open_type,start_time,expire_time');
        $query->with([
            'site' => function ($query) {
                $query->bind(['site_name']);
            },
            'file' => function ($query) {
                $query->field('id,show_src,width,height');
            }
        ]);
        $count = $query->count();
        $list = $query->order('id desc')->page($page, $limit)
            ->hidden(['site'])
            ->order('sort', 'desc')
            ->select();

        return compact('count', 'list');
    }

}