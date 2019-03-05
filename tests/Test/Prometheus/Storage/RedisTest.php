<?php


namespace Prometheus\Storage;

/**
 * @requires extension redis
 */
class RedisTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     * @expectedException \Prometheus\Exception\StorageException
     * @expectedExceptionMessage Can't connect to Redis server
     */
    public function itShouldThrowAnExceptionOnConnectionFailure()
    {
        $redis = new Redis(['host' => 'doesntexist.test']);
        $redis->flushRedis();
    }

}
