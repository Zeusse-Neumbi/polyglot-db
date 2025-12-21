<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateFreshAll extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'migrate:fresh-all 
                            {--seed : Seed the database after migration}
                            {--seeder= : The class name of the root seeder}';

    /**
     * The console command description.
     */
    protected $description = 'Drop all tables (MariaDB) and collections (MongoDB), then re-run all migrations';

    /**
     * MongoDB collections to drop.
     */
    protected array $mongoCollections = [
        'posts',
        'comments',
        'reactions',
        'messages',
        'notifications',
        'sessions_audit',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🗑️  Dropping MongoDB collections...');
        $this->dropMongoCollections();

        $this->info('🔄 Running migrate:fresh for MariaDB...');

        $arguments = [];

        if ($this->option('seed')) {
            $arguments['--seed'] = true;
        }

        if ($seeder = $this->option('seeder')) {
            $arguments['--seeder'] = $seeder;
        }

        $arguments['--force'] = true;

        $this->call('migrate:fresh', $arguments);

        $this->newLine();
        $this->info('✅ All databases refreshed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Drop all MongoDB collections.
     */
    protected function dropMongoCollections(): void
    {
        $mongo = DB::connection('mongodb');

        foreach ($this->mongoCollections as $collection) {
            try {
                $mongo->getCollection($collection)->drop();
                $this->line("  <fg=green>✓</> Dropped: {$collection}");
            } catch (\Exception $e) {
                $this->line("  <fg=yellow>⚠</> Skipped: {$collection} (may not exist)");
            }
        }
    }
}
