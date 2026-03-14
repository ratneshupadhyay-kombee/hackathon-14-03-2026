<?php

namespace App\Services;

use OpenTelemetry\API\Trace\TracerInterface;
use Illuminate\Support\Facades\Log;

class OrderProcessingService
{
    public function __construct(private TracerInterface $tracer) {}

    public function processStatusUpdate(int $orderId, string $newStatus)
    {
        // Build a child span nested under the Livewire request span
        $span = $this->tracer->spanBuilder('Process Order Validation')
            ->setAttribute('order.id', $orderId)
            ->setAttribute('order.new_status', $newStatus)
            ->startSpan();
            
        $scope = $span->activate();

        try {
            Log::info("Validating order {$orderId} for transition to {$newStatus}");
            // Simulate 0.5 seconds of complex external validation via API
            usleep(500000); 
            return true;
        } finally {
            $span->end();
            $scope->detach();
        }
    }
}
