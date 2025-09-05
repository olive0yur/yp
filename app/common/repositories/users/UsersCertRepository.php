<?php

namespace app\common\repositories\users;

use think\facade\Db;
use OSS\Core\OssException;
use app\common\dao\users\UsersCertDao;
use think\exception\ValidateException;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\upload\UploadFileRepository;

class UsersCertRepository extends BaseRepository
{

    public function __construct(UsersCertDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 查询个人认证列表
     */
    public function getList($where, $page, $limit, int $companyId = 0)
    {
        $query = $this->dao->search($where, $companyId)
            ->with([
                'frontFile' => function ($query) {
                    $query->bind(['idcard_front_photo' => 'show_src']);
                },
                'backFile' => function ($query) {
                    $query->bind(['idcard_back_photo' => 'show_src']);
                },
                'user' => function ($query) {
                    $query->bind(['nickname', 'user_mobile' => 'mobile']);
                }
            ]);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->hidden([
                'backFile', 'frontFile', 'user'
            ])
            ->select();
        $statusCount = [
            1 => $this->dao->getSearch(['cert_status' => 1, 'company_id' => $companyId])->count(),
            2 => $this->dao->getSearch(['cert_status' => 2, 'company_id' => $companyId])->count(),
            3 => $this->dao->getSearch(['cert_status' => 3, 'company_id' => $companyId])->count()
        ];
        return compact('list', 'count', 'statusCount');
    }


    public function editInfo($id, array $data)
    {
        /** @var UploadFileRepository $uploadFileRepository */
        $uploadFileRepository = app()->make(UploadFileRepository::class);
        if ($data['idcard_front_photo']) {
            $frontInfo = $uploadFileRepository->getFileData($data['idcard_front_photo']);
            if ($frontInfo) {
                $data['front_file_id'] = $frontInfo['id'];
            }
        }
        if ($data['idcard_back_photo']) {
            $backtInfo = $uploadFileRepository->getFileData($data['idcard_back_photo']);
            if ($backtInfo) {
                $data['back_file_id'] = $backtInfo['id'];
            }
        }
        unset($data['idcard_front_photo'], $data['idcard_back_photo']);
        return $this->dao->update($id, $data);
    }


    /**
     * 删除
     */
    public function delete($id, $data)
    {
//        (app()->make(UploadFileRepository::class))->clearTemplate($data);
//        (app()->make(UsersRepository::class))->clearTemplate($id);
        return $this->dao->delete($id);
    }

    /**
     * 添加申请认证
     */
    public function addCert($data, $userInfo, int $companyId = null)
    {
        return Db::transaction(function () use ($data, $userInfo, $companyId) {
            /** @var UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            $certInfo = $this->dao->getSearch(['company_id' => $companyId, 'number' => $data['number']])->whereIn('cert_status', [1, 2])->find();

            if ($certInfo) {
                $configCertNum = (int)web_config($companyId, 'program.cert.user_cert_num', 0);
                if ($configCertNum > 0) {
                    $certNum = $usersRepository->getSearch(['cert_id' => $certInfo['id']])->count();
                    if ($certNum >= $configCertNum) {
                        throw new ValidateException('同身份证限制注册' . $configCertNum . ' 个用户账号');
                    }
                }
                return $usersRepository->update($data['user_id'], [
                    'cert_id' => $certInfo['id']
                ]);
            } else {
                if ($data['idcard_front_photo']) {
                    /** @var UploadFileRepository $uploadFileRepository */
                    $uploadFileRepository = app()->make(UploadFileRepository::class);
                    $fileInfo = $uploadFileRepository->getFileData($data['idcard_front_photo'], 1, 0);
                    if ($fileInfo['id'] > 0) {
                        $data['front_file_id'] = $fileInfo['id'];
                    }
                    unset($data['idcard_front_photo']);
                }
                if ($data['idcard_back_photo']) {
                    /** @var UploadFileRepository $uploadFileRepository */
                    $uploadFileRepository = app()->make(UploadFileRepository::class);
                    $fileInfo = $uploadFileRepository->getFileData($data['idcard_back_photo'], 1, 0);
                    if ($fileInfo['id'] > 0) {
                        $data['back_file_id'] = $fileInfo['id'];
                    }
                    unset($data['idcard_back_photo']);
                }
                $data['company_id'] = $companyId;
                $certData = $this->dao->create($data);
                return $usersRepository->update($data['user_id'], [
                    'cert_id' => $certData['id']
                ]);
            }
        });
    }

    public function getNumber($number, $company_id = null)
    {
        return $this->dao->search(['number' => $number], $company_id)->count('id');
    }

    /**
     * 个人认证详情
     */
    public function userCertInfo(int $id)
    {
        $data = $this->dao->getSearch(['id' => $id])
            ->with([
                'frontFile' => function ($query) {
                    $query->bind(['idcard_front_photo' => 'show_src']);
                },
                'backFile' => function ($query) {
                    $query->bind(['idcard_back_photo' => 'show_src']);
                }
            ])
            ->hidden(['frontFile', 'backFile', 'front_file_id', 'back_file_id'])
            ->find();
        return $data;
    }
}