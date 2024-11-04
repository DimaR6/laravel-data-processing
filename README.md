# Laravel Data Processing Services

This repository contains example implementations of data processing services in Laravel:

## Features

- Time Series Analysis Service
  - Anomaly detection using z-score
  - Caching implementation
  - Error handling

- Metric Aggregation Service
  - Multiple grouping options (hourly, daily, weekly, monthly)
  - DTO pattern implementation
  - Flexible period filtering

## Installation

1. Clone the repository:
```bash
git clone git@github.com:USERNAME/laravel-data-processing.git
```

2. Install dependencies:
```bash
composer install
```

3. Configure your environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Run migrations:
```bash
php artisan migrate
```

## Usage

Example usage of the Time Series Analysis Service:

```php
$service = new TimeSeriesAnalysisService();
$anomalies = $service->detectAnomalies($dataPoints);
```

Example usage of the Metric Aggregation Service:

```php
$service = new MetricAggregationService();
$metrics = $service->aggregateMetrics($startDate, $endDate, 'daily');
```

## License

The MIT License (MIT). Please see License File for more information.