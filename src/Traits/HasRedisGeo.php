<?php

namespace Ledc\RedisQueue\Traits;

use BadMethodCallException;
use RedisException;
use support\Redis;

/**
 * Redis GEO地理位置
 * @method int|array geoRadius($longitude, $latitude, $radius, $unit, $options = []) 根据用户给定的经纬度坐标来获取指定范围内的地理位置集合
 * @method array geoRadiusByMember($member, $radius, $units, $options = []) 根据储存在位置集合里面的某个地点获取指定范围内的地理位置集合
 */
trait HasRedisGeo
{
    /**
     * 距离单位：米
     */
    final public const UNIT_M = 'm';
    /**
     * 距离单位：千米
     */
    final public const UNIT_KM = 'km';
    /**
     * 距离单位：英里
     */
    final public const UNIT_MI = 'mi';
    /**
     * 距离单位：英尺
     */
    final public const UNIT_FT = 'ft';

    /**
     * GEO地理位置key
     * @var string
     */
    protected string $geoKey;

    /**
     * @param string $geoKey
     * @return HasRedisGeo
     */
    protected function setGeoKey(string $geoKey): static
    {
        $this->geoKey = $geoKey;
        return $this;
    }

    /**
     * 获取
     * @return string
     */
    public function getGeoKey(): string
    {
        return $this->geoKey;
    }

    /**
     * 移除有序集合中指定的成员。
     * @param string $member
     * @return int
     */
    public function zRem(string $member): int
    {
        return Redis::zRem($this->getGeoKey(), $member);
    }

    /**
     * 添加地理位置的坐标
     * @param string $longitude 经度（东西位置）
     * @param string $latitude 纬度（南北位置）
     * @param string $member 成员名
     * @return int
     */
    public function geoAdd(string $longitude, string $latitude, string $member): int
    {
        return Redis::geoAdd($this->getGeoKey(), $longitude, $latitude, $member);
    }

    /**
     * 返回一个或多个位置对象的 geoHash 值
     * @param string|array $members
     * @return array|null
     */
    public function geoHash(string|array $members): ?array
    {
        if (is_string($members)) {
            return Redis::geoHash($this->getGeoKey(), $members);
        } else {
            return Redis::geoHash($this->getGeoKey(), ...$members);
        }
    }

    /**
     * 获取地理位置的坐标
     * @param string|array $members
     * @return array 经纬度数组，二维数组：[[$longitude, $latitude], [$longitude, $latitude], null]
     */
    public function geoPos(string|array $members): array
    {
        if (is_string($members)) {
            return Redis::geoPos($this->getGeoKey(), $members);
        } else {
            return Redis::geoPos($this->getGeoKey(), ...$members);
        }
    }

    /**
     * 返回地理空间集合中两个成员之间的距离
     * @param string $member1 成员1
     * @param string $member2 成员2
     * @param string $unit 距离单位，默认：m米（m:米，km:千米，mi:英里，ft:英尺）
     * @return string
     * @throws RedisException
     */
    public function geoDist(string $member1, string $member2, string $unit = self::UNIT_KM): string
    {
        return Redis::connection()->geodist($this->getGeoKey(), $member1, $member2, $unit);
    }

    /**
     * 以各种方式搜索地理空间集合中的成员
     * @param array|string $position 一个包含经纬度的数组，或一个集合成员的字符串【示例 [$longitude, $latitude] 或 $member】
     * @param array|int|float $shape 一个数字表示搜索的圆的半径，或者一个双元素数组表示要搜索的框的宽度和高度
     * @param string $unit 距离单位
     * @param array $options 其他选项
     * @return array|false
     */
    public function geoSearch(array|string $position, array|int|float $shape, string $unit = self::UNIT_M, array $options = []): array|false
    {
        return Redis::connection()->geosearch($this->getGeoKey(), $position, $shape, $unit, $options);
    }

    /**
     * 在给定区域或范围内搜索地理空间排序集的成员，并将结果存储到新集合中
     * @param string $dst
     * @param string $src
     * @param array|string $position
     * @param array|int|float $shape
     * @param string $unit 距离单位
     * @param array $options 其他选项
     * @return array|false
     */
    public function geoSearchStore(string $dst, string $src, array|string $position, array|int|float $shape, string $unit = self::UNIT_M, array $options = []): array|false
    {
        return Redis::connection()->geosearchstore($dst, $src, $position, $shape, $unit, $options);
    }

    /**
     * 动态调用
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (!in_array($name, ['geoAdd', 'geoHash', 'geoPos', 'geoDist', 'geoRadius', 'geoRadiusByMember'], true)) {
            throw new BadMethodCallException('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
        }
        return Redis::connection()->{$name}($this->getGeoKey(), ...$arguments);
    }
}
