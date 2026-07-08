<?php

namespace App\Services;

class TimelyService
{
    public function __construct(private readonly PendingRequest $client) {}
}
