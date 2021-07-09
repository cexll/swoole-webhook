# swoole-webHook

[![Latest Stable Version](http://poser.pugx.org/cexll/swoole-webhook/v)](https://packagist.org/packages/cexll/swoole-webhook) [![Total Downloads](http://poser.pugx.org/cexll/swoole-webhook/downloads)](https://packagist.org/packages/cexll/swoole-webhook) [![Latest Unstable Version](http://poser.pugx.org/cexll/swoole-webhook/v/unstable)](https://packagist.org/packages/cexll/swoole-webhook) [![License](http://poser.pugx.org/cexll/swoole-webhook/license)](https://packagist.org/packages/cexll/swoole-webhook)

Using WebHooks to automatically pull code

## Support

* [x] GitHub
* [x] Gitee

## 依赖

* php >= 7.2
* ext-swoole >= 4.5

## 安装

```shell
composer create-project cexll/swoole-webhook
```

## 配置

1. 修改配置文件`config.json`

`server`对应的是`Swoole\Http\Server`的相关配置

* `ip`：IP地址
* `port`：端口
* `mode`：启动模式 `SWOOLE_BASE/SWOOLE_PROCESS`
* `settings`：Server的配置

> 设置 daemonize => true 时，程序将转入后台作为守护进程运行。长时间运行的服务器端程序必须启用此项。
如果不启用守护进程，当 ssh 终端退出后，程序将被终止运行。

```json
"server": {
	"ip": "0.0.0.0",
	"port": 19501,
	"mode": 1, // SWOOLE_BASE=1, SWOOLE_PROCESS=2
	"setting": {
		"worker_num": 1,
		"task_worker_num": 1,
		"task_enable_coroutine": true,
		"daemonize": 1 // 后台运行
	}
}
```

`sites`对应的是项目的仓库等信息

分为`github`和`gitee`，`name`是仓库名称，支持多个仓库。

* `secret`/`password`：密钥/密码；`github`使用`secret`，`gitee`的 WebHook 密码使用`password`，签名密钥使用`secret`
* `ref`：分支
* `hook_name`：事件名称；`github`为`push`，`gitee`为`push_hooks`
* `cmds`：需要执行的脚本/命令

```json
"sites": {
	"github": [{
		"name": "cexll/swoole-webhook",
		"secret": "password",
		"ref": "refs/heads/master",
		"hook_name": "push",
		"cmds": [
			"git -C /yourpath/project pull"
		]
	}],
	"gitee": [{
		"name": "cexll/swoole-webhook",
		"password": "password",
		"ref": "refs/heads/master",
		"hook_name": "push_hooks",
		"cmds": [
			"git -C /yourpath/project pull"
		]
	}]
}
```

2. 填写WebHook

URL：`http://ip:port/github` or `http://ip:port/gitee`

Secret/PassWord：对应`config.json`中的`secret/password`

## 启动

```shell
php run.php
```
