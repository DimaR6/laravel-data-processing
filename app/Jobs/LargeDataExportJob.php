<?php

namespace App\Jobs;

use App\Services\DataExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class LargeDataExportJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $model;
    private $filters;
    private $exportPath;
    private $jobId;

    public function __construct($model, array $filters, string $exportPath)
    {
        $this->model = $model;
        $this->filters = $filters;
        $this->exportPath = $exportPath;
        $this->jobId = $this->uniqueId();
    }

    public function handle(DataExportService $dataExportService)
    {
        $totalCount = $this->model::query()->whereArray($this->filters)->count();
        $exportedCount = 0;

        Cache::put("export_progress_{$this->jobId}", 0);

        $dataExportService->exportData(
            $this->model,
            $this->filters,
            $this->exportPath,
            function ($progress) use (&$exportedCount, $totalCount) {
                $exportedCount += $progress;
                $percentComplete = ($exportedCount / $totalCount) * 100;
                Cache::put("export_progress_{$this->jobId}", $percentComplete);
            }
        );

        Cache::put("export_progress_{$this->jobId}", 100);
    }

    public function uniqueId()
    {
        return md5(serialize([
            $this->model,
            $this->filters,
            $this->exportPath,
        ]));
    }

    public function progress()
    {
        return Cache::get("export_progress_{$this->jobId}", 0);
    }
}