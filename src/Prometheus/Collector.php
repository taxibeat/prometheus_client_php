<?php
declare(strict_types = 1);

namespace Prometheus;

use Prometheus\Storage\Adapter;

abstract class Collector
{
    const RE_METRIC_LABEL_NAME = '/^[a-zA-Z_:][a-zA-Z0-9_:]*$/';

    protected $storageAdapter;
    protected $name;
    protected $help;
    protected $labels;
    protected $defaultLabels;

    /**
     * @param Adapter $storageAdapter
     * @param string $namespace
     * @param string $name
     * @param string $help
     * @param array $labels
     */ 
    public function __construct(Adapter $storageAdapter, string $namespace, string $name, string $help, array $labels = [])
    {
        $this->storageAdapter = $storageAdapter;
        $metricName = ($namespace ? $namespace . '_' : '') . $name;
        if (!preg_match(self::RE_METRIC_LABEL_NAME, $metricName)) {
            throw new \InvalidArgumentException("Invalid metric name: '" . $metricName . "'");
        }
        $this->name = $metricName;
        $this->help = $help;
        foreach ($labels as $label) {
            if (!preg_match(self::RE_METRIC_LABEL_NAME, $label)) {
                throw new \InvalidArgumentException("Invalid label name: '" . $label . "'");
            }
        }
        $this->labels = $labels;
    }

    public function applyDefaultLabels(array $defaultLabels = []) : void
    {
        $this->defaultLabels = $defaultLabels;
        $this->setDefaultLabels();
    }

    /**
     * @return string
     */
    public abstract function getType();

    public function getName() : string
    {
        return $this->name;
    }

    public function getLabelNames() : array
    {
        return $this->labels;
    }

    public function getHelp() : string
    {
        return $this->help;
    }

    /**
     * @param array $labels
     */
    protected function assertLabelsAreDefinedCorrectly(array $labels = []) : void
    {
        if (count($labels) != count($this->labels)) {
            throw new \InvalidArgumentException(sprintf('Labels are not defined correctly: ', print_r($labels, true)));
        }
    }

    protected function setDefaultLabels() : void
    {
        if (!empty($this->defaultLabels)) {
            $this->labels = array_merge($this->labels, array_keys($this->defaultLabels));
        }
    }

    protected function setDefaultLabelValues(array $labels) : array
    {
        if (!empty($this->defaultLabels)) {
            return array_merge($labels, array_values($this->defaultLabels));
        }
        return $labels;
    }
}
