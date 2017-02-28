<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Pm\Pms;

$params = getopt('', ['start']);

$pms = new Pms('app.pid', false);
if (!isset($params['start'])) {
    printf("stop\n");
    $pms->stop();
} else {
    $pms->start(2, function () use ($pms) {
        printf("run\n");
        sleep(20);
    });
}
