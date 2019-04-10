<?php

declare(strict_types = 1);

namespace Prometheus;

use GuzzleHttp\Client;

class PushGateway
{
    private $client;

    private $address;

    /**
     * PushGateway constructor.
     * @param $client Client 
     * @param $address string host:port of the push gateway
     */
    public function __construct(Client $client, string $address)
    {       
        $this->client = $client;
        $this->address = $address;
    }

    /**
     * Pushes all metrics in a Collector, replacing all those with the same job.
     * Uses HTTP PUT.
     *
     * @param CollectorRegistry $collectorRegistry
     * @param $job
     * @param $groupingKey
     */
    public function push(CollectorRegistry $collectorRegistry, string $job, array $groupingKey = null) : void
    {
        $this->doRequest($collectorRegistry, $job, $groupingKey, 'put');
    }

    /**
     * Pushes all metrics in a Collector, replacing only previously pushed metrics of the same name and job.
     * Uses HTTP POST.
     *
     * @param CollectorRegistry $collectorRegistry
     * @param $job
     * @param $groupingKey
     */
    public function pushAdd(CollectorRegistry $collectorRegistry, string $job, array $groupingKey = null) : void
    {
        $this->doRequest($collectorRegistry, $job, $groupingKey, 'post');
    }

    /**
     * Deletes metrics from the Pushgateway.
     * Uses HTTP POST.
     *
     * @param $job
     * @param $groupingKey
     */
    public function delete(string $job, array $groupingKey = null) : void
    {
        $this->doRequest(null, $job, $groupingKey, 'delete');
    }

    /**
     * @param CollectorRegistry $collectorRegistry
     * @param $job
     * @param $groupingKey
     * @param $method
     */
    private function doRequest(CollectorRegistry $collectorRegistry = null, string $job, array $groupingKey = null, string $method)
    {
        $url = "http://" . $this->address . "/metrics/job/" . $job;
        if (!empty($groupingKey)) {
            foreach ($groupingKey as $label => $value) {
                $url .= "/" . $label . "/" . $value;
            }
        }
        $requestOptions = [
            'headers' => [
                'Content-Type' => RenderTextFormat::MIME_TYPE,
            ],
            'connect_timeout' => 10,
            'timeout' => 20,
        ];
        if ($method != 'delete') {
            $renderer = new RenderTextFormat();
            $requestOptions['body'] = $renderer->render($collectorRegistry->getMetricFamilySamples());
        }
        $response = $this->client->request($method, $url, $requestOptions);
        $statusCode = $response->getStatusCode();
        if ($statusCode != 202) {
            $msg = "Unexpected status code " . $statusCode . " received from pushgateway " . $this->address . ": " . $response->getBody();
            throw new \RuntimeException($msg);
        }
    }
}
