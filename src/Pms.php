<?php

namespace Pm;

class Pms
{
    private $app = '';
    private $daemon = false;

    public function __construct($app, $daemon)
    {
        $this->app = $app;
        $this->daemon = $daemon;
    }

    public function start($n, $runtine)
    {
        if ($n <= 0) {
            $n = 1;
        }

        if ($this->daemon) {
            Util::daemon();
        }

        $pid = getmypid();
        if (!file_exists($this->app)) {
            file_put_contents($this->app, $pid);
        }

        Pool::getInstance()->start($n, $runtine);
    }

    public function stop()
    {
        if (file_exists($this->app)) {
            $app = $this->app;
            register_shutdown_function(function () use ($app) {
                Util::log('shutdown');

                unlink($app);
            });
        }

        Pool::getInstance()->stop($this->getPid());
    }

    public function restart()
    {
        Pool::getInstance()->restart($this->getPid());
    }

    public function getPid()
    {
        return file_get_contents($this->app);
    }
}
