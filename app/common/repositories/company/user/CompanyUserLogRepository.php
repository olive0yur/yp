<?php

namespace app\common\repositories\company\user;

use app\common\dao\company\user\CompanyUserLogDao;
use app\common\repositories\BaseRepository;

class CompanyUserLogRepository extends BaseRepository
{
    // 日志类型
    const LOG_TYPE = [
        1 => '数据查询',
        2 => '数据添加',
        3 => '数据编辑',
        4 => '数据删除',
        9 => '账号登录',
    ];

    public function __construct(CompanyUserLogDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 添加日志
     *
     * @param int $logType 日志类型
     * @param string $note 日志信息
     * @param array $otherData 日志操作数据
     * @param int $adminId 管理员id
     * @param int $companyId 企业id
     * @param string $token 操作token
     * @return \app\common\dao\BaseDao|\think\Model
     */
    public function addLog($logType, $note, $otherData = [], $adminId = 0, $companyId = 0, $token = '')
    {
        /**
         * @var CompanyUserRepository $adminUserRepository
         */
        $adminUserRepository = app()->make(CompanyUserRepository::class);
        $data = [
            'remark' => $note,
            'log_type' => $logType,
            'admin_id' => $adminId ?: $adminUserRepository->getLoginAdminId(),
            'company_id' => $companyId ?: $adminUserRepository->getLoginCompanyId(),
            'other_data' => serialize($otherData),
            'log_ip' => app('request')->ip(),
            'log_url' => app('request')->url(),
            'token' => $token ? $token : $adminUserRepository->getLoginAdminToken()
        ];

        return $this->dao->create($data);
    }

    /**
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     */
    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->searchJoin($where);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->order('id', 'desc')
            ->select();
        foreach ($list as $k => $v) {
            $list[$k]['username'] = ($v['adminUser']['account'] ?? '') . ' | ' . ($v['adminUser']['username'] ?? '');
            $list[$k]['log_type'] = self::LOG_TYPE[$v['log_type']] ?? '';
        }
        return compact('list', 'count');
    }
}