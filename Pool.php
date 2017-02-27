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

    private function __construct()
    {
        $running = true;
        pcntl_signal(SIGTERM, function (&$running) {
            $running = false;
            Util::log("shutdown");
        }, false);
        $this->running = $running;
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
        for ($i = 0; $i < $this->num; $i++) {
            call_user_func($this->routine);
        }
    }

    public function execute()
    {
        $running = $this->running;
        $stop = false;
        pcntl_signal(SIGUSR1, function () use (&$stop) {
            $stop = true;
        });


        $this->run();

        while ($running) {
            if ($stop) {
                pcntl_signal_dispatch();
                $this->kill(SIGTERM);
                $stop = false;
            }

            $this->wait();

            usleep(40000);
        }
    }

    public function kill($signal)
    {
        foreach ($this->workers as $k => $v) {
            posix_kill($k, $signal);
        }
    }

    public function wait()
    {
        // 等待子进程返回的状态
        // $pid 发生错误时返回-1
        foreach ($this->workers as $k => $v) {
            $pid = pcntl_waitpid($k, $status, WNOHANG);

            if ($pid > 0) {
                printf($pid . "\n");
                unset($this->workers[$pid]);
            }
        }
    }

    public function clean()
    {
        // SIG_BLOCK = 0
        foreach ($this->workers as $k => $v) {
            if (!posix_kill($k, 0)) {
                unset($this->workers[$k]);
            }
        }
    }

    public function getWorkersNum()
    {
        return count($this->workers);
    }
}
