<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Metric;
use Carbon\CarbonPeriod;
use App\DTOs\AggregatedMetricDTO;
use Illuminate\Support\Carbon;

class MetricAggregationService
{
    /**
     * 
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $groupBy
     * @return Collection
     */
    public function aggregateMetrics(
        Carbon $startDate, 
        Carbon $endDate, 
        string $groupBy = 'daily'
    ): Collection {
        $period = CarbonPeriod::create($startDate, $endDate);
        
        $metrics = Metric::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
            
        return collect($period->toArray())
            ->map(function ($date) use ($metrics, $groupBy) {
                return $this->aggregateForPeriod($date, $metrics, $groupBy);
            })
            ->filter();
    }
    
    /**
     * 
     *
     * @param Carbon $date
     * @param Collection $metrics
     * @param string $groupBy
     * @return AggregatedMetricDTO|null
     */
    private function aggregateForPeriod(
        Carbon $date, 
        Collection $metrics, 
        string $groupBy
    ): ?AggregatedMetricDTO {
        $periodMetrics = $this->filterMetricsForPeriod($metrics, $date, $groupBy);
        
        if ($periodMetrics->isEmpty()) {
            return null;
        }
        
        return new AggregatedMetricDTO([
            'period' => $date->format('Y-m-d'),
            'total' => $periodMetrics->sum('value'),
            'average' => $periodMetrics->avg('value'),
            'min' => $periodMetrics->min('value'),
            'max' => $periodMetrics->max('value'),
            'count' => $periodMetrics->count(),
            'metrics' => $periodMetrics
        ]);
    }
    
    /**
     * 
     *
     * @param Collection $metrics
     * @param Carbon $date
     * @param string $groupBy
     * @return Collection
     */
    private function filterMetricsForPeriod(
        Collection $metrics, 
        Carbon $date, 
        string $groupBy
    ): Collection {
        return $metrics->filter(function ($metric) use ($date, $groupBy) {
            $metricDate = Carbon::parse($metric->created_at);
            
            return match($groupBy) {
                'hourly' => $metricDate->format('Y-m-d H') === $date->format('Y-m-d H'),
                'daily' => $metricDate->format('Y-m-d') === $date->format('Y-m-d'),
                'weekly' => $metricDate->weekOfYear === $date->weekOfYear,
                'monthly' => $metricDate->format('Y-m') === $date->format('Y-m'),
                default => throw new \InvalidArgumentException("Невідомий тип групування: {$groupBy}")
            };
        });
    }
}