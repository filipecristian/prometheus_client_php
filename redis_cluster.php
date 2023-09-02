<?php

use Prometheus\CollectorRegistry;
use Prometheus\Storage\RedisCluster;

require_once './vendor/autoload.php';

try {
    $collector = new CollectorRegistry(
        new RedisCluster(
            'PicPay QA',
            [
                'qa-redis-cluster-0001-001.bfh66s.0001.use1.cache.amazonaws.com:6379',
                'qa-redis-cluster-0002-001.bfh66s.0001.use1.cache.amazonaws.com:6379',
                'qa-redis-cluster-0003-001.bfh66s.0001.use1.cache.amazonaws.com:6379',
                'qa-redis-cluster-0004-001.bfh66s.0001.use1.cache.amazonaws.com:6379',
            ]
        )
    );

    $collector->getCounter(
        'TESTING_123',
        'qtd_requests'
    )->inc();
} catch (Throwable $e) {
    echo $e->getMessage() . PHP_EOL;
}
