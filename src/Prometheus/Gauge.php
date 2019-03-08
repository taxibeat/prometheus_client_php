<?php

declare(strict_types = 1);

namespace Prometheus;

use Prometheus\Storage\Adapter;

class Gauge extends Collector
{
    const TYPE = 'gauge';

    /**
     * @param float $value  e.g. 123
     * @param array $labels e.g. ['status', 'opcode']
     */
    public function set($value, array $labels = []) : void
    {
        $labels = $this->setDefaultLabelValues($labels);

        $this->assertLabelsAreDefinedCorrectly($labels);

        $this->storageAdapter->updateGauge(
            [
                'name' => $this->getName(),
                'help' => $this->getHelp(),
                'type' => $this->getType(),
                'labelNames' => $this->getLabelNames(),
                'labelValues' => $labels,
                'value' => $value,
                'command' => Adapter::COMMAND_SET,
            ]
        );
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return self::TYPE;
    }

    public function inc(array $labels = []) : void
    {
        $this->incBy(1, $labels);
    }

    /**
     * @param float $value
     * @param array $labels
     *
     * @return void
     */
    public function incBy(float $value, array $labels = []) : void
    {
        $labels = $this->setDefaultLabelValues($labels);

        $this->assertLabelsAreDefinedCorrectly($labels);

        $this->storageAdapter->updateGauge(
            [
                'name' => $this->getName(),
                'help' => $this->getHelp(),
                'type' => $this->getType(),
                'labelNames' => $this->getLabelNames(),
                'labelValues' => $labels,
                'value' => $value,
                'command' => Adapter::COMMAND_INCREMENT_FLOAT,
            ]
        );
    }

    public function dec(array $labels = []) : void
    {
        $this->decBy(1, $labels);
    }

    public function decBy(int $value, $labels = []) : void
    {
        $this->incBy(-$value, $labels);
    }
}
