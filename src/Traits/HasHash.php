<?php

namespace Ledc\RedisQueue\Traits;

use Closure;
use Illuminate\Redis\Connections\Connection;
use support\Redis;

/**
 * Redis哈希表
 */
trait HasHash
{
    /**
     * 【获取】完整的哈希表键名
     * @return string
     */
    abstract public static function getKey(): string;

    /**
     * 获取存储在哈希表中指定字段的值
     * - 如果存在，则返回字段的值
     * - 如果不存在，则调用回调函数，然后把返回值写入缓存
     * @param string $member
     * @param Closure|null $fn 获取值回调
     * @param Closure|null $refresh 是否刷新回调，回调内返回布尔值，true刷新缓存，false不刷新缓存
     * @return mixed
     */
    public static function hGetOrSet(string $member, ?Closure $fn = null, ?Closure $refresh = null): mixed
    {
        $value = static::connection()->hGet(static::getKey(), $member);
        if ($value && $fn && $refresh && call_user_func($refresh, $member)) {
            // 需同时满足刷新条件：1.字段存在 2.存在回调 3.回调返回true
            goto refresh;
        }
        if (!$value && $fn) {
            refresh:
            $value = call_user_func($fn, $member);
            if (!$value) {
                return null;
            }
            static::hSet($member, $value);
            return $value;
        }
        return $value ?: null;
    }

    /**
     * 获取存储在哈希表中指定字段的值
     * @param string $member
     * @return false|mixed
     */
    public static function hGet(string $member): mixed
    {
        return static::connection()->hGet(static::getKey(), $member);
    }

    /**
     * 将哈希表中的字段 field 的值设为 value
     * @param string $member 字段名称
     * @param string|int|float $value 字段值
     * @return false|int
     */
    public static function hSet(string $member, string|int|float $value): false|int
    {
        return static::connection()->hSet(static::getKey(), $member, $value);
    }

    /**
     * 获取存储在哈希表中指定字段的值
     * - json_decode解码
     * @param string $member 字段名称
     * @return array|string|int|float|bool|null
     */
    public static function getJsonDecode(string $member): array|string|int|float|bool|null
    {
        $value = static::connection()->hGet(static::getKey(), $member);
        return false === $value || null === $value ? null : json_decode($value, true);
    }

    /**
     * 将哈希表中的字段 field 的值设为 value
     * - json_encode编码
     * @param string $member 字段名称
     * @param array|string|int|float|bool $value 字段值
     * @return false|int
     */
    public static function setJsonEncode(string $member, array|string|int|float|bool $value): false|int
    {
        return static::connection()->hSet(static::getKey(), $member, json_encode($value, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 判断哈希表的指定字段是否存在
     * @param string $field 字段名称
     * @return bool
     */
    public static function has(string $field): bool
    {
        return static::connection()->hExists(static::getKey(), $field);
    }

    /**
     * 删除哈希表字段
     * @param string $field 字段名称
     * @param string ...$fields
     * @return false|int
     */
    public static function del(string $field, string ...$fields): false|int
    {
        return static::connection()->hDel(static::getKey(), $field, ...$fields);
    }

    /**
     * 获取Redis连接
     * @return Connection|\Redis
     */
    public static function connection(): Connection|\Redis
    {
        return Redis::connection();
    }
}
