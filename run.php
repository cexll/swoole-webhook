<?php

declare(strict_types=1);
/**
 * This file is part of swoole-webhook.
 *
 * @link     https://github.com/cexll/swoole-webhook
 * @document https://github.com/cexll/swoole-webhook
 * @license  https://github.com/cexll/swoole-webhook/blob/master/LICENSE
 */
require __DIR__ . '/vendor/autoload.php';

\Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);

new \Cexll\Swoole\Webhook\SwooleWebhook();
