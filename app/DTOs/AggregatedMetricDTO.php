<?php

namespace App\DTOs;

class AggregatedMetricDTO
{
    public function __construct(
        private readonly array $data
    ) {}

    public function toArray(): array
    {
        return $this->data;
    }

    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }
}