<?php

namespace Pm;

/**
 * process pool
 *
 */
class Pool
{
    private static $instance = null;
    private $workers = [];
    private $num = 1;
    private $routine = null;
    private $running = true;

    protected function __construct()
    {
        pcntl_signal(SIGHUP, SIG_IGN);
        pcntl_signal(SIGPIPE, SIG_IGN);
        pcntl_signal(SIGINT, [$this, 'quit'], false);
        pcntl_signal(SIGQUIT, [$this, 'quit'], false);
        pcntl_signal(SIGTERM, [$this, 'quit'], false);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function start($n, $runtine)
    {
        $this->num = $n;

        if (is_callable($runtine)) {
            $this->routine = function () use ($runtine) {
                // 子进程则执行
                if (0 === $this->fork()) {
                    $runtine();
                    exit(0);
                }
            };
        } elseif (is_string($runtine)) {
            $this->routine = function () use ($runtine) {
                if (0 === $this->fork()) {
                    if (false === pcntl_exec($runtine, $args, $envs)) {
                        exit();
                    }
                }
            };
        } elseif (is_array($runtine)) {
            $this->routine = function () use ($runtine) {
                if (0 === $this->fork()) {
                    call_user_func_array($runtine);
                    exit(0);
                }
            };

        } else {

        }

        $this->execute();
    }

    public function stop($pid)
    {
        posix_kill($pid, SIGTERM);

        while (posix_kill($pid, 0)) {
            echo(".");
            sleep(1);
        }

        echo "done\n";
    }

    public function restart($pid)
    {
        posix_kill($pid, SIGUSR1);
    }

    public function fork()
    {
        $pid = pcntl_fork();

        if ($pid > 0) {
            $this->workers[$pid] = date('Y-m-d H:i:s');
        }

        return $pid;
    }

    public function run()
    {
        $real_num = $this->num - count($this->workers);
        for ($i = 0; $i < $real_num; $i++) {
            call_user_func($this->routine);
        }
    }

    public function execute()
    {
        $stop = false;
        pcntl_signal(SIGUSR1, function () use (&$stop) {
            Util::log('get sigusr1');
            $stop = true;
        });


        while ($this->running) {
            $this->run();
            pcntl_signal_dispatch();

            if ($stop) {
                $this->kill(SIGTERM);
                $stop = false;
                Util::log('restart');
            }

            $this->wait();

            usleep(40000);
        }

        $this->shutdown();
    }

    public function kill($signal)
    {
        foreach ($this->workers as $k => $v) {
            posix_kill($k, $signal);
        }
    }

    public function wait($block = false)
    {
        // 等待子进程返回的状态, $pid 发生错误时返回-1
        foreach ($this->workers as $k => $v) {
            $pid = pcntl_waitpid($k, $status, $block ? 0 : WNOHANG);

            if ($pid > 0) {
                unset($this->workers[$pid]);
            }
        }
    }

    public function clean()
    {
        // SIG_IGN 忽略信号处理,pcntl_signal(SIGHUP, SIG_IGN);
        // SIG_BLOCK = 0
        foreach ($this->workers as $k => $v) {
            if (!posix_kill($k, 0)) {
                unset($this->workers[$k]);
            }
        }
    }

    public function shutdown()
    {
        Util::log('shut');
        $this->kill(SIGTERM);
        $this->wait();
        $this->clean();
    }

    public function quit()
    {
        Util::log('quit');
        $this->running = false;
    }

    public function getWorkersNum()
    {
        return count($this->workers);
    }
}
