<?php

namespace app\common\repositories\system;

use think\exception\ValidateException;
use app\common\dao\system\SystemPactDao;
use app\common\repositories\BaseRepository;

/**
 * Class AgreementRepository
 *
 * @package app\common\repositories\system
 * @mixin SystemPactDao
 */
class SystemPactRepository extends BaseRepository
{
    // 协议类型
    const PACT_TYPE = [
        1 => '用户协议',
        2 => '隐私政策',
        3 => '注销协议',
        4 => '关于我们',
//        5 => '加入我们',
//        6 => '购买须知',
//        7 => '寄售须知',
//        8 => '温馨提示',
        9 => '邀请规则',
//        10 => '积分规则',
        12 => '玩法规则',
        13 => '公会玩法',
        14 => '我的客服',
        15 => '好友矿场介绍',
        16 => '进阶矿场介绍',
        17 => '高阶矿场介绍',
        18 => '盲盒玩法',
    ];

    public function __construct(SystemPactDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where,$page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('id,pact_type,content,add_time,edit_time')
            ->select();
        return compact('list', 'count');
    }

    /**
     * 获取协议信息
     * @param int $pactType 协议类型
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPactInfo(int $pactType,int $companyId = null)
    {
        if (!in_array($pactType, array_keys(self::PACT_TYPE))) {
            throw new ValidateException('类型错误');
        }
        $info = $this->dao->getSearch(['company_id'=>$companyId])->where([
            'pact_type' => $pactType
        ])->find();
        return $info;
    }

    /**
     * 添加
     */
    public function addInfo($data)
    {
        return $this->dao->create($data);
    }

    /**
     * 编辑
     */
    public function editInfo($id,array $data)
    {
        return $this->dao->update($id,$data);
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

}