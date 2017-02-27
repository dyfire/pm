<?php

namespace Pm;

/**
 * tools class
 *
 */
class Util
{
    public static function daemon()
    {
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

        return true;
    }

    public static function log($data)
    {
        file_put_contents('/home/wangjianwen/study/fork/pm/example/log', sprintf('[%s] %s \n', date('Y-m-d H:i:s'), $data, FILE_APPEND));
    }
}
