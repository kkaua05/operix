<?php

namespace App\Services\Ixc;

interface ScraperRunner
{
    /**
     * Runs the scraper and returns its decoded JSON payload.
     *
     * @return array<string, mixed>
     *
     * @throws IxcScraperException
     */
    public function run(): array;
}
