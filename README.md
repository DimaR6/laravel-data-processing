# Laravel Data Processing Services

This repository contains example implementations of data processing services in Laravel.

## Features

### Metric Aggregation Service
- Supports various grouping options (hourly, daily, weekly, monthly)
- Utilizes the DTO pattern for data structuring
- Provides flexible period filtering

### Large Data Export
- The `LargeDataExportJob` class handles large dataset exports asynchronously using a queue
- Includes progress tracking functionality to keep clients informed about the export status
- The progress is stored in the cache and can be accessed using the `progress()` method

### Crypto Wallet Factory
- The `CryptoWalletFactory` allows you to create and manage different types of cryptocurrency wallets
- Supports easy integration of new wallet types by updating the configuration
- Decouples your application's cryptocurrency functionality from the underlying wallet implementations

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

### Metric Aggregation Service
```php
$service = new MetricAggregationService();
$metrics = $service->aggregateMetrics($startDate, $endDate, 'daily');
```

### Large Data Export
```php
$job = dispatch(new LargeDataExportJob(
    User::class,
    ['active' => true, 'created_at' => '2023-01-01'],
    storage_path('exports/users.csv')
));

$progress = $job->progress();
```

### Crypto Wallet Factory
```php

    private $walletFactory;

    public function __construct(WalletFactory $walletFactory)
    {
        $this->walletFactory = $walletFactory;
    }

    $code = $withdraw->crypto->code;
    $blockchainConnection = $this->walletFactory->createWalletInstance($code);

    $blockchainConnection->getBalance();

```

## License

The MIT License (MIT). Please see License File for more information.