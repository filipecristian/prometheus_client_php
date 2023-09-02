<?php

declare(strict_types=1);

namespace Prometheus\Storage;

use InvalidArgumentException;
use Prometheus\Counter;

class RedisCluster extends Redis
{
    /**
     * @var string
     */
    private static $prefix = 'PROMETHEUS_';

    /**
     * @var \RedisCluster
     */
    private $cluster;

    private $options;

    /**
     * @throws \RedisClusterException
     */
    public function __construct(
        $name,
        $seeds,
        $timeout = null,
        $readTimeout = null,
        $persistence = false,
        $auth = null,
        $options = []
    ) {
        $this->cluster = new \RedisCluster(
            $name,
            $seeds
        );
        $this->options = $options;
    }

    /**
     * @param mixed[] $data
     */
    public function updateCounter(array $data): void
    {
        $this->cluster->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_NONE);
        $metaData = $data;
        unset($metaData['value'], $metaData['labelValues'], $metaData['command']);
        $this->->eval(
            <<<LUA
local result = redis.call(ARGV[1], KEYS[1], ARGV[3], ARGV[2])
local added = redis.call('sAdd', KEYS[2], KEYS[1])
if added == 1 then
    redis.call('hMSet', KEYS[1], '__meta', ARGV[4])
end
return result
LUA
            ,
            [
                $this->toMetricKey($data),
                self::$prefix . Counter::TYPE . self::PROMETHEUS_METRIC_KEYS_SUFFIX,
                $this->getRedisCommand($data['command']),
                $data['value'],
                json_encode($data['labelValues']),
                json_encode($metaData),
            ],
            2
        );
    }

    /**
     * @param mixed[] $data
     * @return string
     */
    private function toMetricKey(array $data): string
    {
        return implode(':', [self::$prefix, $data['type'], $data['name']]);
    }

    /**
     * @param int $cmd
     * @return string
     */
    private function getRedisCommand(int $cmd): string
    {
        switch ($cmd) {
            case Adapter::COMMAND_INCREMENT_INTEGER:
                return 'hIncrBy';
            case Adapter::COMMAND_INCREMENT_FLOAT:
                return 'hIncrByFloat';
            case Adapter::COMMAND_SET:
                return 'hSet';
            default:
                throw new InvalidArgumentException("Unknown command");
        }
    }
}
