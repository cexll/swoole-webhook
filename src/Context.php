<?php

declare(strict_types=1);
/**
 * This file is part of swoole-webhook.
 *
 * @link     https://github.com/cexll/swoole-webhook
 * @document https://github.com/cexll/swoole-webhook
 * @license  https://github.com/cexll/swoole-webhook/blob/master/LICENSE
 */
namespace Cexll\Swoole\Webhook;

use Swoole\Coroutine;

class Context
{
    protected static $pool = [];

    public static function get($key)
    {
        $cid = Coroutine::getCid();
        if ($cid < 0) {
            return null;
        }
        return self::$pool[$cid][$key] ?? null;
    }

    public static function put($key, $item): void
    {
        $cid = Coroutine::getCid();
        if ($cid > 0) {
            self::$pool[$cid][$key] = $item;
        }
    }

    public static function delete($key = null): void
    {
        $cid = Coroutine::getCid();
        if ($cid > 0) {
            if ($key) {
                unset(self::$pool[$cid][$key]);
            } else {
                unset(self::$pool[$cid]);
            }
        }
    }
}
