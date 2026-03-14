<?php

namespace App\Services;

/**
 * File-based Prometheus metrics service.
 * Persists counters/histograms to storage/metrics.json — no APCu or Redis needed.
 */
class MetricsService
{
    protected string $storageFile;
    /** @var array<string, float> */
    protected array $data = [];

    public function __construct()
    {
        $this->storageFile = storage_path('metrics.json');
        $this->load();
    }

    protected function load(): void
    {
        if (file_exists($this->storageFile)) {
            $raw = @json_decode(file_get_contents($this->storageFile), true);
            $this->data = is_array($raw) ? $raw : [];
        }
    }

    protected function save(): void
    {
        file_put_contents($this->storageFile, json_encode($this->data), LOCK_EX);
    }

    public function increment(string $metric, float $by = 1): void
    {
        $this->load();
        $this->data[$metric] = ($this->data[$metric] ?? 0) + $by;
        $this->save();
    }

    public function observe(string $metric, float $value): void
    {
        $this->load();
        // Store sum and count for avg calculation; also track buckets
        $this->data["{$metric}_sum"]   = ($this->data["{$metric}_sum"]   ?? 0) + $value;
        $this->data["{$metric}_count"] = ($this->data["{$metric}_count"] ?? 0) + 1;
        $this->save();
    }

    public function render(): string
    {
        $this->load();

        $lines = '';

        // --- Counters ---
        $counters = [
            'laravel_orders_created_total'    => 'Total orders created',
            'laravel_products_created_total'  => 'Total products created',
            'laravel_login_failed_total'      => 'Total failed login attempts',
            'laravel_validation_errors_total' => 'Total validation errors',
            'laravel_http_requests_total'     => 'Total HTTP requests',
            'laravel_http_errors_total'       => 'Total HTTP errors',
        ];

        foreach ($counters as $name => $help) {
            $lines .= "# HELP {$name} {$help}\n# TYPE {$name} counter\n";
            $lines .= "{$name} " . ($this->data[$name] ?? 0) . "\n";
        }

        // --- Histogram (response time) ---
        $lines .= "# HELP laravel_http_response_time_seconds HTTP response time\n";
        $lines .= "# TYPE laravel_http_response_time_seconds histogram\n";
        $sum   = $this->data['laravel_http_response_time_seconds_sum']   ?? 0;
        $count = $this->data['laravel_http_response_time_seconds_count'] ?? 0;
        // Emit minimal histogram buckets so histogram_quantile works
        foreach ([0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10] as $le) {
            $bucketKey = "laravel_http_response_time_seconds_bucket_le_{$le}";
            $lines .= "laravel_http_response_time_seconds_bucket{le=\"{$le}\"} " . ($this->data[$bucketKey] ?? $count) . "\n";
        }
        $lines .= "laravel_http_response_time_seconds_bucket{le=\"+Inf\"} {$count}\n";
        $lines .= "laravel_http_response_time_seconds_sum {$sum}\n";
        $lines .= "laravel_http_response_time_seconds_count {$count}\n";

        return $lines;
    }

    /**
     * Compatibility shim — returns a proxy that handles getOrRegisterCounter/Histogram calls.
     */
    public function getRegistry(): RegistryProxy
    {
        return new RegistryProxy($this);
    }
}

class RegistryProxy
{
    public function __construct(private MetricsService $metrics) {}

    public function getOrRegisterCounter(string $ns, string $name, string $help, array $labelNames = []): CounterProxy
    {
        return new CounterProxy($this->metrics, "{$ns}_{$name}");
    }

    public function getOrRegisterHistogram(string $ns, string $name, string $help, array $labelNames = [], array $buckets = []): HistogramProxy
    {
        return new HistogramProxy($this->metrics, "{$ns}_{$name}");
    }
}

class CounterProxy
{
    public function __construct(private MetricsService $metrics, private string $name) {}

    public function inc(array $labels = []): void
    {
        $this->metrics->increment($this->name);
    }

    public function incBy(float $amount, array $labels = []): void
    {
        $this->metrics->increment($this->name, $amount);
    }
}

class HistogramProxy
{
    public function __construct(private MetricsService $metrics, private string $name) {}

    public function observe(float $value, array $labels = []): void
    {
        $this->metrics->observe($this->name, $value);
        // Track bucket counts
        foreach ([0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10] as $le) {
            if ($value <= $le) {
                $this->metrics->increment("{$this->name}_bucket_le_{$le}");
            }
        }
    }
}
