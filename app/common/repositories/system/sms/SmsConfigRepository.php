<?php

namespace app\common\repositories\system\sms;

use app\common\dao\system\sms\SmsConfigDao;
use app\common\repositories\BaseRepository;
use app\common\services\CacheService;
use think\facade\Cache;

/**
 * Class SmsConfigRepository
 *
 * @package app\common\repositories\system\sms
 * @mixin SmsConfigDao
 */
class SmsConfigRepository extends BaseRepository
{

    public function __construct(SmsConfigDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 保存配置
     *
     * @param $data
     * @return \app\common\dao\BaseDao|int|\think\Model
     * @throws \think\db\exception\DbException
     */
    public function save(int $companyId, $data)
    {
        $smsConfig = $this->getSmsConfig($companyId);
        if ($smsConfig) {
            $res = $this->dao->update($smsConfig['id'], $data);
        } else {
            $data['company_id'] = $companyId;
            $res = $this->dao->create($data);
        }
        $this->clearCache($companyId);
        return $res;
    }

    /**
     * 获取短信配置
     *
     * @return mixed
     */
    public function getSmsConfig(int $companyId = 0)
    {
        return CacheService::init($companyId)->remember('sms_config_' . $companyId, function () use ($companyId) {
            $res = $this->dao->getWhere([
                'company_id' => $companyId
            ]);
            if ($res) {
                return $res->toArray();
            }
            return $res;
        });
    }

    /**
     * 清除缓存
     */
    public function clearCache($companyId)
    {
        CacheService::delete('sms_config_' . $companyId);
    }
}