<?php

namespace Pm; 

class Pms {
    private $app = ''; 
    private $daemon = false;

    public function __construct($app, $daemon) {
        $this->app = $app;
        $this->daemon = $daemon;
    }   

    public function start($n, $runtine) {
        if ($this->daemon) {
            Util::daemon();
        }   
            
        $pid = getmypid();
        if (!file_exists($this->app)) {
            file_put_contents($this->app, $pid);
        }   

        $app = $this->app;
        register_shutdown_function(function() use ($app) {
            printf("shut down\n");
            unlink($app);
        }); 

        Pool::getInstance()->start($n, $runtine);
    }   

    public function stop() {
        if (file_exists($this->app)) {
            $pid = file_get_contents($this->app);
        }   

        Pool::getInstance()->stop($pid);
    }   
}
