<?php

namespace app\common\model;


use think\db\BaseQuery;
use think\Model;

abstract class BaseModel extends Model
{

    abstract public static function tablePk(): ?string;

    abstract public static function tableName(): string;

    public function __construct(array $data = [])
    {
        $this->pk = static::tablePk();
        $this->name = static::tableName();
        parent::__construct($data);
    }

    public static function getInstance(): self
    {
        return new static();
    }

    public static function getDB(array $scope = [])
    {
        return self::getInstance()->db($scope);
    }

}