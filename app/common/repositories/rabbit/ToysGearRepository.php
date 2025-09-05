<?php

namespace app\common\repositories\rabbit;

use app\common\dao\rabbit\ToysGearDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\sign\SignAbcRepository;
use app\common\repositories\sign\SignRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use think\exception\ValidateException;

/**
 * Class ToysGearRepository
 * @package app\common\repositories\rabbit
 * @mixin ToysGearDao
 */
class ToysGearRepository extends BaseRepository
{

    const TYPE = [
        1 => '左耳',
        2 => '右耳',
        3 => '眼睛',
//        3 => '脸鼻',
        4 => '面部',
        5 => '身体',
        6 => '左腿',
        7 => '右腿',
        8 => '左手',
        9 => '右手',
        10 => '裤子',
    ];

    public function __construct(ToysGearDao $dao)
    {
        $this->dao = $dao;

    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->withAttr('type_name', function ($value, $data) {
                return self::TYPE[$data['type']];
            })
            ->with(['level'])
            ->append(['type_name'])
            ->order('id desc')
            ->select();
        return compact('count', 'list');
    }


    public function editInfo($info, $data)
    {
        if ($data['cover']) {
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1, 0);
            if ($fileInfo['id'] != $info['id']) {
                $data['file_id'] = $fileInfo['id'];
            }
        }

        if ($data['head_cover']) {
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['head_cover'], 1, 0);
            if ($fileInfo['id'] != $info['id']) {
                $data['head_file_id'] = $fileInfo['id'];
            }
        }
        unset($data['cover']);
        unset($data['head_cover']);
        return $this->dao->update($info['id'], $data);
    }

    public function addInfo($companyId, $data)
    {
        if ($data['cover']) {
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1, 0);
            if ($fileInfo['id'] > 0) {
                $data['file_id'] = $fileInfo['id'];
            }
        }
        if ($data['head_cover']) {
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['head_cover'], 1, 0);
            if ($fileInfo['id'] > 0) {
                $data['head_file_id'] = $fileInfo['id'];
            }
        }
        unset($data['cover']);
        unset($data['head_cover']);
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->with([
                'level' => function ($q) {
                    $q->field('id,title');
                },
                'cover' => function ($q) {
                    $q->bind(['picture' => 'show_src']);
                },
                'headerCover' => function ($q) {
                    $q->bind(['head_picture' => 'show_src']);
                }
            ])
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


    public function getCong($userInfo, $companyId)
    {
        $list = $this->dao->search([], $companyId)->select();

        /** @var SignRepository $signRepository */
        $signRepository = app()->make(SignRepository::class);
        $day = $signRepository->search(['uuid' => $userInfo['id']], $companyId)->order('create_at desc')->value('day');
        $total = 10;
        /** @var SignAbcRepository $signAbcRepository */
        $signAbcRepository = app()->make(SignAbcRepository::class);
        $abcDay = $signAbcRepository->search(['uuid' => $userInfo['id']], $companyId)->whereDay('create_at')->count('id');
        return compact('list', 'day', 'total', 'abcDay');
    }

}