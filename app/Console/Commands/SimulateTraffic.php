<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TrafficSimulationService;

class SimulateTraffic extends Command
{
    protected $signature = 'traffic:simulate 
                            {--duration=60 : Duration in seconds to run the simulation}
                            {--active-percent=20 : Percentage of users to be active during simulation}';

    protected $description = 'Simulate real-time traffic on the platform for a given duration.';

    protected TrafficSimulationService $service;

    public function __construct(TrafficSimulationService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        $duration = (int) $this->option('duration');
        $activePercent = (int) $this->option('active-percent');

        $this->info("Starting simulation: {$duration}s with {$activePercent}% active users...");

        $totalActions = $this->service->runSimulation($duration, $activePercent);

        $this->info("Simulation completed. Total actions performed: {$totalActions}");

        return Command::SUCCESS;
    }
}
