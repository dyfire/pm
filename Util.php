<?php

namespace Pm; 

/**
 * tools class
 *  
 */
class Util {
    public static function daemon() {
        umask(0);
    
        $pid = pcntl_fork();
    
        if ($pid > 0) {
            exit();
        } elseif ($pid < 0) {
            return false;
        } else { 
        }
            
        $pid = pcntl_fork();

        if ($pid > 0) {
            exit();
        } elseif ($pid < 0) {
            return false;
        } else {

        }
    
        $sid = posix_setsid();
        
        if (!$sid) {
            return false;
        }   

        file_put_contents($pid, $pid);
        register_shutdown_function(function() use ($pid) {
            unlink($pid);
        });

        return true;
    }
}
