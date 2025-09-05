<?php

namespace app\common\repositories\system\sms;

use app\common\dao\system\sms\SmsLogDao;
use app\common\repositories\BaseRepository;

/**
 * Class SmsLogRepository
 *
 * @package app\common\repositories\system\sms
 * @mixin SmsLogDao
 */
class SmsLogRepository extends BaseRepository
{

    public function __construct(SmsLogDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(int $companyId = null, array $where = [], int $page = null, int $limit = null)
    {
        $query = $this->dao->search($companyId, $where);
        $count = $query->count();
        $list = $query->when($page !== null, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->order('id', 'desc')->select();
        foreach ($list as $k => $v) {
            $list[$k]['sms_type'] = config('sms.template_type.' . $v['sms_type'] . '.name');
        }
        return compact('count', 'list');
    }
}