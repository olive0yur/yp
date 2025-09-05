<?php

namespace app\common\repositories\wall;

use app\common\dao\wall\WallDao;
use app\common\dao\users\UsersPushDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\users\UsersRepository;
use app\common\repositories\users\UsersWallRepository;
use think\exception\ValidateException;
use think\facade\Db;

class WallRepository extends BaseRepository
{

    public function __construct(WallDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $uuid, $page, $limit, int $companyId = null)
    {

        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }

    public function addInfo(int $companyId = null, array $data = [])
    {
        return Db::transaction(function () use ($data, $companyId) {
            if ($companyId) $data['company_id'] = $companyId;
            $data['add_time'] = date('Y-m-d H:i:s');
            $userInfo = $this->dao->create($data);
            return $userInfo;
        });
    }


    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id, $companyId = null)
    {

        $data = $this->dao->search([], $companyId)
            ->where('id', $id)
            ->find();
        return $data;
    }

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

    public function getApiList(array $where, $uuid, $page, $limit, int $companyId = null)
    {

        $query = $this->dao->search($where, $companyId)->where(['status' => 1]);
        $count = $query->count();
        $list = $query->page($page, $limit)
            // ->order('id', 'desc')
            ->select();

        $usersWallRepository = app()->make(UsersWallRepository::class);
        //判断用户参与状态
        foreach ($list as &$item) {
            $item['status'] = 0;
            $item['diff_time'] = '';
            $item['is_get'] = 0;
            $nowTime = time();
            $userWall = $usersWallRepository->search([], $companyId)->where(['uuid' => $uuid, 'wall_id' => $item['id']])->find();
            if ($userWall) {
                if ($nowTime > strtotime($userWall['get_time'])) {
                    $item['diff_time'] = '可领取';
                    $item['is_get'] = 1;
                } else if ($userWall['status'] == 2) {
                    $item['diff_time'] = '已领取';
                } else {
                    $item['diff_time'] = time_diff(time(), strtotime($userWall['get_time']), 2) . '分钟后';
                }
                $item['status'] = $userWall['status'];
            }
        }
        return compact('list', 'count');
    }

    //购买靓号
    public function buyWall($id, $user, $companyId = null)
    {
        //上一层是否开启
        $wall = $this->getDetail($id, $companyId);
        if (!$wall) throw new ValidateException('阶段不存在');

        $usersWallRepository = app()->make(UsersWallRepository::class);
        $usersWall = $usersWallRepository->search([], $companyId)->where(['uuid' => $user['id']])->order('stage desc')->find();
        //第一层特殊判断
        if ($wall['stage'] == 1 && $usersWall) {
            if ($wall['stage'] == $usersWall['stage']) {
                throw new ValidateException('已开启过此阶段');
            }
        }
        if ($wall['stage'] > 1 && (!$usersWall || $wall['stage'] - 1 != $usersWall['stage'])) {
            throw new ValidateException('请先完成上一阶段');
        }
        if ($usersWall && $usersWall['status'] != 2) {
            throw new ValidateException('请先完成上一阶段');
        }
        //晶核余额
        if ($user['gold'] < $wall['price']) {
            throw new ValidateException('晶核不足');
        }

        $usersRepository = app()->make(UsersRepository::class);
        //减少余额 开启阶段
        return Db::transaction(function () use ($user, $wall, $usersWall, $usersRepository, $usersWallRepository, $companyId) {
            $usersRepository->batchGoldChange($user['id'], 3, '-' . $wall['price'], ['remark' => '开启第' . $wall['stage'] . '阶梯', 'company_id' => $companyId], 4, $companyId);

            $usersWallRepository->addInfo($companyId, [
                'uuid' => $user['id'],
                'wall_id' => $wall['id'],
                'stage' => $wall['stage'],
                'value' => $wall['value'],
                'status' => 1,
                'get_time' => date('Y-m-d H:i:s', time() + ($wall['time'] * 60)),
            ]);
            return true;
        });
    }

    public function getAward($id, $user, $companyId = null)
    {
        $usersWallRepository = app()->make(UsersWallRepository::class);
        $usersWall = $usersWallRepository->search([], $companyId)->where(['uuid' => $user['id'], 'wall_id' => $id])->order('stage desc')->find();

        if ($usersWall['status'] != 1) {
            throw new ValidateException('阶段状态异常');
        }
        if (time() < strtotime($usersWall['get_time'])) {
            throw new ValidateException('还未到领取时间');
        }

        //发放晶核 更新状态
        $usersRepository = app()->make(UsersRepository::class);
        //减少余额 开启阶段
        return Db::transaction(function () use ($user, $usersWall, $usersRepository, $usersWallRepository, $companyId) {
            $usersRepository->batchGoldChange($user['id'], 4, $usersWall['value'], ['remark' => '获取第' . $usersWall['stage'] . '阶梯奖励', 'company_id' => $companyId], 4, $companyId);
            $usersWallRepository->editInfo($usersWall, ['status' => 2]);
            return true;
        });
    }

    public function getApiDetail(int $id, $uuid, $companyId = null)
    {

        $data = $this->dao->search(['uuid' => $uuid], $companyId)
            ->where('id', $id)
            ->find();
        return $data;
    }
}
