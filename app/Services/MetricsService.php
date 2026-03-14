<?php

namespace App\Services;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\APC;
use Prometheus\Storage\Adapter;

class MetricsService
{
    protected CollectorRegistry $registry;

    public function __construct()
    {
        // Use APC adapter for persistence across multiple requests.
        // InMemory resets on every request, which is why Prometheus was seeing "No Data".
        $this->registry = new CollectorRegistry(new APC());
    }

    public function getRegistry(): CollectorRegistry
    {
        return $this->registry;
    }

    public function render(): string
    {
        $renderer = new RenderTextFormat();
        return $renderer->render($this->registry->getMetricFamilySamples());
    }
}
