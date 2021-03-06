<?php

namespace Test;

use GuzzleHttp\Client;

use Prometheus\CollectorRegistry;
use Prometheus\PushGateway;
use Prometheus\Storage\APC;

class BlackBoxPushGatewayTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function pushGatewayShouldWork()
    {
        $adapter = new APC();
        $registry = new CollectorRegistry($adapter);

        $counter = $registry->registerCounter('test', 'some_counter', 'it increases', ['type']);
        $counter->incBy(6, ['blue']);

        $httpClient = new Client();

        $pushGateway = new PushGateway($httpClient, 'pushgateway:9091');
        $pushGateway->push($registry, 'my_job', ['instance' => 'foo']);

        $metrics = $httpClient->get("http://pushgateway:9091/metrics")->getBody()->getContents();
        $this->assertContains(
            '# HELP test_some_counter it increases
# TYPE test_some_counter counter
test_some_counter{instance="foo",job="my_job",type="blue"} 6',
            $metrics
        );

        $pushGateway->delete('my_job', ['instance' => 'foo']);

        $httpClient = new Client();
        $metrics = $httpClient->get("http://pushgateway:9091/metrics")->getBody()->getContents();
        $this->assertNotContains(
            '# HELP test_some_counter it increases
# TYPE test_some_counter counter
test_some_counter{instance="foo",job="my_job",type="blue"} 6',
            $metrics
        );
    }
}
