<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Pm\Pms;

$params = getopt('', ['start::']);
$status = isset($params['start']) ? $params['start'] : '';
$pms = new Pms('app.pid', false);

switch ($status) {
    case 'start':
        $pms->start(1, function () use ($pms) {
            printf("run\n");
            sleep(600);
        });
        break;
    case 'stop':
        printf("stop\n");
        $pms->stop();
        break;
    case 'restart':
        $pms->restart();
        break;
    default:
        die(<<<USAGE
usage:
    start:\tpm --start]
    stop:\tpm --stop]
    reload:\tpm --restart]

USAGE
        );
}
