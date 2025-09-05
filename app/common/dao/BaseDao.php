<?php

namespace app\common\dao;


use app\common\model\BaseModel;
use app\helper\Time;
use think\Collection;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;
use think\Model;

abstract class BaseDao
{
    abstract protected function getModel(): string;


    /**
     * @return string
     */
    public function getPk()
    {
        return ($this->getModel())::tablePk();
    }

    /**
     * @param int $id
     * @return bool
     */
    public function exists(int $id)
    {
        return $this->fieldExists($this->getPk(), $id);
    }


    /**
     * @param $field
     * @param $value
     * @param int|null $except
     * @return bool
     */
    public function fieldExists($field, $value, ?int $except = null): bool
    {
        $query = ($this->getModel())::getDB()->where($field, $value);
        if (!is_null($except)) $query->where($this->getPk(), '<>', $except);
        return $query->count() > 0;
    }


    /**
     * 企业检测数据存不存
     *
     * @param int $companyId
     * @param $id
     * @return bool
     */
    public function companyExists(int $companyId, int $id)
    {
        return $this->companyFieldExists($companyId, $this->getPk(), $id);
    }


    public function companyFieldExists(int $companyId, $field, $value, $except = null)
    {
        return ($this->getModel())::getDB()
                ->when($except, function ($query, $except) use ($field) {
                    $query->where($this->getPk(), '<>', $except);
                })
                ->where('company_id', $companyId)
                ->where($field, $value)->count() > 0;
    }



    /**
     * @param array $data
     * @return self|Model
     */
    public function create(array $data)
    {
        return ($this->getModel())::create($data);
    }


    /**
     * @param int $id
     * @param array $data
     * @return int
     * @throws DbException
     */
    public function update(int $id, array $data)
    {
        return ($this->getModel())::getDB()->where($this->getPk(), $id)->update($data);
    }

    /**
     * @param array $ids
     * @param array $data
     * @return int
     * @throws DbException
     */
    public function updates(array $ids, array $data)
    {
        return ($this->getModel())::getDB()->whereIn($this->getPk(), $ids)->update($data);
    }


    /**
     * @param int $id
     * @return int
     * @throws DbException
     */
    public function delete(int $id)
    {
        return ($this->getModel())::getDB()->where($this->getPk(), $id)->delete();
    }

    /**
     * 条件删除
     * @param array $where
     * @return int
     * @throws DbException
     */
    public function whereDelete(array $where)
    {
        return ($this->getModel())::getDB()->where($where)->delete();
    }

    /**
     * 条件编辑
     * @param array $where
     * @param array $data
     * @return int
     * @throws DbException
     */
    public function whereUpdate(array $where, array $data)
    {
        return ($this->getModel())::getDB()->where($where)->update($data);
    }


    /**
     * @param int $id
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function get($id)
    {
        return ($this->getModel())::getInstance()->find($id);
    }

    /**
     * @param array $where
     * @param string $field
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getWhere(array $where, string $field = '*', array $with = [])
    {
        return ($this->getModel())::getInstance()->where($where)->when($with, function ($query) use ($with) {
            $query->with($with);
        })->field($field)->find();
    }

    /**
     * @param array $where
     * @param string $field
     * @return Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function selectWhere(array $where, string $field = '*')
    {
        return ($this->getModel())::getInstance()->where($where)->field($field)->select();
    }

    /**
     * @param int $id
     * @param array $with
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getWith(int $id, $with = [])
    {
        return ($this->getModel())::getInstance()->with($with)->find($id);
    }


    /**
     * @param array $data
     * @return int
     */
    public function insertAll(array $data)
    {
        return ($this->getModel())::getDB()->insertAll($data);
    }

    /**
     * TODO 通过条件判断是否存在
     * @param array $where
     */
    public function getWhereCount(array $where)
    {
        return ($this->getModel()::getDB())->where($where)->count();
    }

    public function existsWhere($where)
    {
        return ($this->getModel())::getDB()->where($where)->count() > 0;
    }

    /**
     * TODO 查询,如果不存在就创建
     * @param array $where
     * @return array|Model|null
     */
    public function findOrCreate(array $where)
    {
        $res = ($this->getModel()::getDB())->where($where)->find();
        if (!$res) $res = $this->getModel()::create($where);
        return $res;
    }

    /**
     * TODO 搜索
     * @param $where
     * @return BaseModel
     */
    public function getSearch(array $where)
    {
        foreach ($where as $key => $item) {
            if ($item !== '') {
                $keyArray[] = $key;
                $whereArr[$key] = $item;
            }
        }
        if (empty($keyArray)) {
            return ($this->getModel())::getDB();
        } else {
            return ($this->getModel())::withSearch($keyArray, $whereArr);
        }
    }

    /**
     * TODO 自增
     * @param array $id
     * @param string $field
     * @param int $num
     * @return mixed
     */
    public function incField(int $id, string $field, $num = 1)
    {
        return ($this->getModel()::getDB())->where($this->getPk(), $id)->inc($field, $num)->update();
    }

    /**
     * TODO 自减
     * @param array $id
     * @param string $field
     * @param int $num
     * @return mixed
     */
    public function decField(int $id, string $field, $num = 1)
    {
        return ($this->getModel()::getDB())
            ->where($this->getPk(), $id)
            ->where($field, '>=', $num)
            ->dec($field, $num)->update();
    }

    /**
     * 字段修改
     *
     * @param int $id
     * @param string $field
     * @param mixed $value
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function updateFiled(int $id, string $field, int $value)
    {
        return ($this->getModel()::getDB())->where($this->getPk(), $id)->update([
            $field => $value
        ]);
    }

    /**
     * 构建时间搜索条件
     *
     * @param Query $query 查询对象
     * @param string|array $time 查询时间
     * @param string $field 字段名
     * @return Query
     */
    protected function timeSearchBuild(Query $query, $time, string $field)
    {
        if (is_array($time)) {
            $query->whereBetweenTime($field, $time[0], $time[1]);
        } else {
            if ($time == 'today') {
                $query->whereBetweenTime($field, Time::today()[0], Time::today()[1]);
            } elseif ($time == 'yesterday') {
                $query->whereBetweenTime($field, Time::yesterday()[0], Time::yesterday()[1]);
            } elseif ($time == 'lately7') {
                $query->whereBetweenTime($field, Time::dayToNow(7, true)[0], Time::dayToNow(7, true)[1]);
            } elseif ($time == 'lately30') {
                $query->whereBetweenTime($field, Time::dayToNow(30, true)[0], Time::dayToNow(30, true)[1]);
            } elseif ($time == 'month') {
                $query->whereBetweenTime($field, Time::month()[0], Time::month()[1]);
            } elseif ($time == 'year') {
                $query->whereBetweenTime($field, Time::year()[0], Time::year()[1]);
            } else {
                $times = explode(' - ', $time);
                if (count($times) > 1) {
                    $query->whereBetweenTime($field, $times[0], $times[1]);
                } else {
                    if (strlen($time) > 10) {
                        $query->whereTime($field, 'between', [$time, $time]);
                    } else {
                        $count = substr_count($time, '-');
                        if ($count === 0) {
                            $query->whereYear($field, $time);
                        } elseif ($count === 1) {
                            $query->whereMonth($field, $time);
                        } elseif ($count === 2) {
                            $query->whereDay($field, $time);
                        }
                    }
                }
            }
        }

        return $query;
    }
}
