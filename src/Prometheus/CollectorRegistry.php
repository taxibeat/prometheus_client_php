<?php

declare(strict_types = 1);

namespace Prometheus;

use Prometheus\Exception\MetricNotFoundException;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\Redis;

class CollectorRegistry
{
    /**
     * @var CollectorRegistry
     */
    private static $defaultRegistry;

    /**
     * @var array
     */
    private $defaultLabels;

    /**
     * @var Adapter
     */
    private $storageAdapter;
    /**
     * @var Gauge[]
     */
    private $gauges = [];
    /**
     * @var Counter[]
     */
    private $counters = [];
    /**
     * @var Histogram[]
     */
    private $histograms = [];

    public function __construct(Adapter $adapter)
    {
        $this->storageAdapter = $adapter;
    }

    /**
     * @param array $defaultLabels
     *
     * @return void
     */
    public function applyDefaultLabels(array $defaultLabels = []) : void
    {
        $this->defaultLabels = $defaultLabels;
    }

    /**
     * @return CollectorRegistry
     */
    public static function getDefault() : CollectorRegistry
    {
        if (!self::$defaultRegistry) {
            return self::$defaultRegistry = new static(new Redis());
        }

        return self::$defaultRegistry;
    }

    /**
     * @return MetricFamilySamples[]
     */
    public function getMetricFamilySamples() : array
    {
        return $this->storageAdapter->collect();
    }

    /**
     * @param string $namespace e.g. cms
     * @param string $name      e.g. duration_seconds
     * @param string $help      e.g. The duration something took in seconds.
     * @param array  $labels    e.g. ['controller', 'action']
     *
     * @return Gauge
     *
     * @throws MetricsRegistrationException
     */
    public function registerGauge(string $namespace, string $name, string $help, array $labels = []) : Gauge
    {
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (isset($this->gauges[$metricIdentifier])) {
            throw new MetricsRegistrationException("Metric already registered");
        }
        $this->gauges[$metricIdentifier] = new Gauge(
            $this->storageAdapter,
            $namespace,
            $name,
            $help,
            $labels
        );

        if ($this->defaultLabels) {
            $this->gauges[$metricIdentifier]->applyDefaultLabels($this->defaultLabels);
        }

        return $this->gauges[$metricIdentifier];
    }

    /**
     * @param string $namespace
     * @param string $name
     *
     * @return Gauge
     *
     * @throws MetricNotFoundException
     */
    public function getGauge(string $namespace, string $name) : Gauge
    {
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (!isset($this->gauges[$metricIdentifier])) {
            throw new MetricNotFoundException("Metric not found:" . $metricIdentifier);
        }

        return $this->gauges[$metricIdentifier];
    }

    /**
     * @param string $namespace e.g. cms
     * @param string $name      e.g. duration_seconds
     * @param string $help      e.g. The duration something took in seconds.
     * @param array  $labels    e.g. ['controller', 'action']
     *
     * @return Gauge
     *
     * @throws MetricsRegistrationException
     */
    public function getOrRegisterGauge(string $namespace, string $name, string $help, array $labels = []) : Gauge
    {
        try {
            $gauge = $this->getGauge($namespace, $name);
        } catch (MetricNotFoundException $e) {
            $gauge = $this->registerGauge($namespace, $name, $help, $labels);
        }

        return $gauge;
    }

    /**
     * @param string $namespace e.g. cms
     * @param string $name      e.g. requests
     * @param string $help      e.g. The number of requests made.
     * @param array  $labels    e.g. ['controller', 'action']
     *
     * @return Counter
     *
     * @throws MetricsRegistrationException
     */
    public function registerCounter(string $namespace, string $name, string $help, array $labels = []) : Counter
    {
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (isset($this->counters[$metricIdentifier])) {
            throw new MetricsRegistrationException("Metric already registered");
        }
        $this->counters[$metricIdentifier] = new Counter(
            $this->storageAdapter,
            $namespace,
            $name,
            $help,
            $labels
        );

        if ($this->defaultLabels) {
            $this->counters[$metricIdentifier]->applyDefaultLabels($this->defaultLabels);
        }

        return $this->counters[self::metricIdentifier($namespace, $name)];
    }

    /**
     * @param string $namespace
     * @param string $name
     *
     * @return Counter
     *
     * @throws MetricNotFoundException
     */
    public function getCounter(string $namespace, string $name) : Counter
    {
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (!isset($this->counters[$metricIdentifier])) {
            throw new MetricNotFoundException("Metric not found:" . $metricIdentifier);
        }

        return $this->counters[self::metricIdentifier($namespace, $name)];
    }

    /**
     * @param string $namespace e.g. cms
     * @param string $name      e.g. requests
     * @param string $help      e.g. The number of requests made.
     * @param array  $labels    e.g. ['controller', 'action']
     *
     * @return Counter
     *
     * @throws MetricsRegistrationException
     */
    public function getOrRegisterCounter(string $namespace, string $name, string $help, array $labels = []) : Counter
    {
        try {
            $counter = $this->getCounter($namespace, $name);
        } catch (MetricNotFoundException $e) {
            $counter = $this->registerCounter($namespace, $name, $help, $labels);
        }

        return $counter;
    }

    /**
     * @param string $namespace e.g. cms
     * @param string $name      e.g. duration_seconds
     * @param string $help      e.g. A histogram of the duration in seconds.
     * @param array  $labels    e.g. ['controller', 'action']
     * @param array  $buckets   e.g. [100, 200, 300]
     *
     * @return Histogram
     *
     * @throws MetricsRegistrationException
     */
    public function registerHistogram(string $namespace, string $name, string $help, array $labels = [], array $buckets = null) : Histogram
    {
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (isset($this->histograms[$metricIdentifier])) {
            throw new MetricsRegistrationException("Metric already registered");
        }
        $this->histograms[$metricIdentifier] = new Histogram(
            $this->storageAdapter,
            $namespace,
            $name,
            $help,
            $labels,
            $buckets
        );

        if ($this->defaultLabels) {
            $this->histograms[$metricIdentifier]->applyDefaultLabels($this->defaultLabels);
        }

        return $this->histograms[$metricIdentifier];
    }

    /**
     * @param string $namespace
     * @param string $name
     *
     * @return Histogram
     *
     * @throws MetricNotFoundException
     */
    public function getHistogram(string $namespace, string $name) : Histogram
    {
        $metricIdentifier = self::metricIdentifier($namespace, $name);
        if (!isset($this->histograms[$metricIdentifier])) {
            throw new MetricNotFoundException("Metric not found:" . $metricIdentifier);
        }

        return $this->histograms[self::metricIdentifier($namespace, $name)];
    }

    /**
     * @param string $namespace e.g. cms
     * @param string $name      e.g. duration_seconds
     * @param string $help      e.g. A histogram of the duration in seconds.
     * @param array  $labels    e.g. ['controller', 'action']
     * @param array  $buckets   e.g. [100, 200, 300]
     *
     * @return Histogram
     */
    public function getOrRegisterHistogram(string $namespace, string $name, string $help, array $labels = [], array $buckets = null) : Histogram
    {
        try {
            $histogram = $this->getHistogram($namespace, $name);
        } catch (MetricNotFoundException $e) {
            $histogram = $this->registerHistogram($namespace, $name, $help, $labels, $buckets);
        }

        return $histogram;
    }

    private static function metricIdentifier(string $namespace, string $name) : string
    {
        return $namespace . ":" . $name;
    }
}
