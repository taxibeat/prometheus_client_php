<?php

namespace Test\Prometheus;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Prometheus\CollectorRegistry;
use Prometheus\PushGateway;
use Prometheus\Storage\APC;

class PushGatewayTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function itShouldPushToGateway()
    {
        $mock = new MockHandler([
            new Response(202, []),
        ]);

        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        $collectorRegistry = new CollectorRegistry(new APC);

        $gateway = new PushGateway($client, 'mocked.pushgateway.com::1234');
        $grouping = [
            'label1' => 'val1',
            'label2' => 'val2',
        ];
        $gateway->push($collectorRegistry, 'job', $grouping);
        $this->assertCount(1, $container);
    }

    /**
     * @test
     */
    public function itShouldPushAddToGateway()
    {
        $mock = new MockHandler([
            new Response(202, []),
        ]);

        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        $collectorRegistry = new CollectorRegistry(new APC);

        $gateway = new PushGateway($client, 'mocked.pushgateway.com::1234');
        $gateway->pushAdd($collectorRegistry, 'job', []);
        $this->assertCount(1, $container);
    }

    /**
     * @test
     */
    public function itShouldDelete()
    {
        $mock = new MockHandler([
            new Response(202, []),
        ]);

        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        $gateway = new PushGateway($client, 'mocked.pushgateway.com::1234');
        $gateway->delete('job', []);
        $this->assertCount(1, $container);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unexpected status code
     */
    public function itShouldThrowUnexpectedStatus()
    {
        $mock = new MockHandler([
            new Response(200, []),
        ]);

        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        $collectorRegistry = new CollectorRegistry(new APC);

        $gateway = new PushGateway($client, 'mocked.pushgateway.com::1234');
        $gateway->push($collectorRegistry, 'job', []);
    }
}
