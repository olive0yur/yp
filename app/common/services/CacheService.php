<?php

namespace app\common\services;

use think\facade\Cache;

class CacheService extends Cache
{
    /**
     * 初始化缓存信息
     *
     * @param int $companyId 企业ID
     * @return \think\cache\TagSet
     */
    public static function init($companyId = 0)
    {
        $tag = 'sys_cache';
        if ($companyId) {
            $tag .= '_company_' . $companyId;
        }
        return self::tag($tag);
    }
}