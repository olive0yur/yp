<?php
declare (strict_types=1);

namespace app\command;


use app\common\model\mine\MineUserModel;
use app\common\repositories\game\LevelRepository;
use app\common\repositories\game\LevelTeamRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class MemberLevel extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('MemberLevel')
            ->setDescription('团队等级');
    }

    protected function execute(Input $input, Output $output)
    {
        $userRepository = app()->make(UsersRepository::class);
        $userpushRepository = app()->make(UsersPushRepository::class);
        $levelRepository = app()->make(LevelTeamRepository::class);
        $userPushRepository = app()->make(UsersPushRepository::class);
        $mineUserRepository = app()->make(MineUserRepository::class);
        $mineRepository = app()->make(MineRepository::class);
        $list = $userRepository->search([], 74)->order('id desc')->select();
        foreach ($list as $user) {
            $count = app()->make(UsersPoolRepository::class)->search(['uuid' => $user['id'], 'status' => 1])->count('id');
            if ($user['team_vip'] == -1) {
                if ($count > 0) {
                    $userRepository->update($user['id'], ['team_vip' => 0]);
                    $user['team_vip'] = 0;
                }
            }
            if ($user['team_vip'] == 0) {
                if ($count <= 0) {
                    $userRepository->update($user['id'], ['team_vip' => -1]);
                    $user['team_vip'] = -1;
                }
            }

            $level = $levelRepository->search([])->order('level desc')->select();
            foreach ($level as $value) {
                $trueZHi = false;
                $trueTuan = false;
                if ($value['under_push'] > 0) {
                    $childs = $userPushRepository->search(['parent_id' => $user['id'], 'levels' => 1])->column('user_id');
                    $childUser = $userRepository->search([])->whereIn('id', $childs)
                        ->where('team_vip', '>=', $value['under_require'])->count('id');

                    if ($childUser >= $value['under_push']) {
                        $trueZHi = true;
                    }
                }
                if ($value['team_push'] > 0) {
                    $childs = $userPushRepository->search(['parent_id' => $user['id']])->whereIn('levels', [1, 2])->column('user_id');
                    $childUser = $userRepository->search([])->whereIn('id', $childs)->count('id');
                    if ($childUser >= $value['team_push']) {
                        $trueTuan = true;
                    }
                }

                if ($user['team_vip'] == -1) {
                    $trueTuan = false;
                    $trueZHi = false;
                }

                if ($trueTuan && $trueZHi) {
                    $userRepository->update($user['id'], ['team_vip' => $value['level']]);
                    break 1;//123
                } else {
                    if ($value['level'] == 1) {
                        $userRepository->update($user['id'], ['team_vip' => 0]);
                        $count = app()->make(UsersPoolRepository::class)->search(['uuid' => $user['id'], 'status' => 1])->count('id');
                        if ($count <= 0) {
                            $userRepository->update($user['id'], ['team_vip' => -1]);
                        }
                    }
                }
            }

        }

    }
}