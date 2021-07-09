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

class run
{
    /** @var swoole_http_request */
    protected $server;

    /** @var array */
    protected $config;


    public function __construct()
    {
        $this->config = self::getConfig();
        $this->server = new swoole_http_server($this->config['server']['ip'], $this->config['server']['port'], $this->config['server']['mode']);
        if (isset($this->config['server']['setting'])) {
            if (array_key_exists('daemonize', $this->config['server']['setting']) && $this->config['server']['setting']['daemonize'] === 1) {
                $this->config['server']['setting']['pid_file'] = __DIR__ . '/server.pid';
            }
            $this->server->set($this->config['server']['setting']);
        }
        $this->server->on('request', function (swoole_http_request $request, swoole_http_response $response) {
            $this->onRequest($request, $response);
        });

        $this->server->on('WorkerStart', static function () {
        });

        $this->server->on('task', function ($serv, $task) {
            \Swoole\Coroutine::create(function () use ($serv, $task) {
                $this->onTask($serv, $task);
            });
        });

        $this->output();
        $this->server->start();
    }

    public function output(): void
    {
        echo "Run SwooleWebHook", \PHP_EOL;
    }

    public function onRequest(swoole_http_request $request, swoole_http_response $response): void
    {
        try {
            $this->log($request);
            if (empty($request->rawcontent()) || $request->rawcontent() === "") {
                $response->end('Not Found!');
                return;
            }
            switch ($request->server['request_uri']) {
                case '/gitee':
                    $this->server->task([
                        'type' => 'Gitee',
                        'data' => $request->rawcontent(),
                        'header' => $request->header,
                    ]);
                    break;
                case '/github':
                    $this->server->task([
                        'type' => 'Github',
                        'data' => $request->rawcontent(),
                        'header' => $request->header,
                    ]);
                    break;
                default:
                    $response->write('<p>Not Found!</p>');
                    break;
            }
        } catch (\Throwable $ex) {
            $response->write($ex->getMessage());
        }
        $response->end('swoole webhook!');
    }


    public function parseGitee($data): void
    {
        $data['data'] = json_decode($data['data'], true);
        foreach ($this->config['sites']['gitee'] as $item) {
            if (isset($item['name']) && $item['name'] !== $data['data']['project']['path_with_namespace']) {
                continue;
            }
            if (isset($item['password']) && $item['password'] !== $data['data']['password']) {
                continue;
            }
            if (isset($item['hook_name']) && $item['hook_name'] !== $data['data']['hook_name']) {
                continue;
            }
            if (isset($item['ref']) && $item['ref'] !== $data['data']['ref']) {
                continue;
            }
            if (!isset($item['cmds']) || !is_array($item['cmds'])) {
                break;
            }
            foreach ($item['cmds'] as $cmd) {
                \Swoole\Coroutine::exec($cmd);
            }
            echo 'gitee hook', \PHP_EOL;
            return;
        }
        echo 'gitee no action', \PHP_EOL;
    }

    public function parseGithub($data): void
    {
        $rawData = $data['data'];
        $data['data'] = json_decode($data['data'], true);
        foreach ($this->config['sites']['github'] as $item) {
            if (isset($item['name']) && $item['name'] !== $data['data']['repository']['full_name']) {
                continue;
            }
            if (isset($item['password'])) {
                [$algo, $hash] = explode('=', $data['header']['x-hub-signature']);
                $myHash = hash_hmac($algo, $rawData, $item['password']);
                if ($hash !== $myHash) {
                    continue;
                }
            }
            if (isset($item['hook_name']) && $item['hook_name'] !== $data['header']['x-github-event']) {
                continue;
            }
            if (isset($item['ref']) && $item['ref'] !== $data['data']['ref']) {
                continue;
            }
            if (!isset($item['cmds']) || !is_array($item['cmds'])) {
                break;
            }
            foreach ($item['cmds'] as $cmd) {
                \Swoole\Coroutine::exec($cmd);
            }
            echo 'github hook', \PHP_EOL;

            return;
        }
        echo 'github no action', \PHP_EOL;
    }

    public function onTask($serv, $task): void
    {
        switch ($task->data['type']) {
            case 'Gitee':
                $this->parseGitee($task->data);
                break;
            case 'Github':
                $this->parseGithub($task->data);
                break;
        }
    }

    public function log(swoole_http_request $request): void
    {
        @file_put_contents(__DIR__ . '/http.log', json_encode([
            'date' => date('Y-m-d H:i:s'),
            'method' => $request->getMethod(),
            'host' => $request->header['host'],
            'url' => $request->server['path_info'],
            'ip' => $request->header['x-real-ip'],
            'data' => $request->rawcontent(),
        ], JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    public static function getConfig()
    {
        return json_decode(file_get_contents(__DIR__ . '/config.json'), true);
    }
}


\Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
(new run());
