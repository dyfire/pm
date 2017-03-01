<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Pm\Pms;

$params = getopt('', ['start::']);
$status = isset($params['start']) ? $params['start'] : '';
$pms = new Pms('app.pid', true);

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
    start:\tpm --start --app={app/command} -n {n} [--pid={pid} [--pidfile={pidfile}]] [--wait-bootstrap={senconds}]
    stop:\tpm --stop [--pid={pid} [--pidfile={pidfile}]]
    reload:\tpm --reload [--pid={pid} [--pidfile={pidfile}]]

USAGE
        );
}
