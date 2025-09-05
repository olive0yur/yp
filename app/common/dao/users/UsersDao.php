<?php

namespace app\common\dao\users;

use app\common\dao\BaseDao;
use app\common\model\users\UsersModel;

class UsersDao extends BaseDao
{

    public function search(array $where, int $companyId = null)
    {
        return UsersModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->where('nickname|mobile|regist_ip', 'like', '%' . trim($where['keywords']) . '%');
            })
            ->when(isset($where['mobile']) && $where['mobile'] !== '', function ($query) use ($where) {
                $query->where('mobile', $where['mobile']);
            })
            ->when(isset($where['is_open']) && $where['is_open'] !== '', function ($query) use ($where) {
                $query->where('is_open', $where['is_open']);
            })
            ->when(isset($where['is_self']) && $where['is_self'] !== '', function ($query) use ($where) {
                $query->where('is_self', $where['is_self']);
            })
            ->when(isset($where['is_cert']) && $where['is_cert'] !== '', function ($query) use ($where) {
                $query->where('cert_id', 'in', function ($query) use ($where) {
                    $query->name('users_cert')
                        ->where('cert_status', $where['is_cert'])
                        ->field('id');
                });
            })
            ->when(isset($where['is_author']) && $where['is_author'] !== '', function ($query) use ($where) {
                $query->where(['is_author' => $where['is_author'], 'status' => 1]);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where(['status' => $where['status']]);
            })
            ->when(isset($where['user_code']) && $where['user_code'] !== '', function ($query) use ($where) {
                $query->where(['user_code' => $where['user_code']]);
            })
            ->when(isset($where['is_success']) && $where['is_success'] !== '', function ($query) use ($where) {
                $query->where(['is_success' => $where['is_success']]);
            })
            ->when(isset($where['group_id']) && $where['group_id'] !== '', function ($query) use ($where) {
                $query->where('group_id', (int)$where['group_id']);
            })
            ->when(isset($where['is_top']) && $where['is_top'] !== '', function ($query) use ($where) {
                $query->where('is_top', (int)$where['is_top']);
            })
            ->when(isset($where['sys_labels']) && $where['sys_labels'] !== '', function ($query) use ($where) {
                if (!is_array($where['sys_labels'])) {
                    $where['sys_labels'] = array_filter(explode(',', $where['sys_labels']));
                }
                $query->where(function ($query) use ($where) {
                    foreach ($where['sys_labels'] as $v) {
                        $query->whereOr(function ($query) use ($v) {
                            $query->whereRaw('CONCAT(\',\',label_id,\',\') LIKE \'%,' . (int)$v . ',%\'');
                        });
                    }
                });
            })
            ->when(isset($where['reg_time']) && $where['reg_time'] !== '', function ($query) use ($where) {
                $times = explode(' - ', trim($where['reg_time']));
                $query->where('add_time','between', [$times[0], $times[1]]);
//                $this->timeSearchBuild($query, $where['reg_time'], 'add_time');
            });
    }

    public function getUserByMobile(string $account, int $companyId = null)
    {
        return $this->getModel()::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('mobile', $account)
            ->with([
                'tjrInfo' => function ($query) {
                    $query->with([
                        'tjrOne' => function ($query) {
                            $query->bind(['mobile', 'nickname']);
                        }
                    ]);
                },
                'avatars' => function ($query) {
                    $query->bind(['avatar' => 'show_src']);
                }
            ])
            ->find();
    }

    /**
     * @return UsersModel
     */
    protected function getModel(): string
    {
        return UsersModel::class;
    }

    public function clearTemplate($id)
    {
        $res = $this->getModel()::getDB()->where('cert_id', $id)->find();
        return $this->update($res['id'], [
            'cert_id' => 0
        ]);
    }
}
