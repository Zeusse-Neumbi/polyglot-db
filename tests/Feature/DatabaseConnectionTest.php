<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! filter_var(getenv('RUN_DB_INTEGRATION_TESTS'), FILTER_VALIDATE_BOOLEAN)) {
            $this->markTestSkipped(
                'Set RUN_DB_INTEGRATION_TESTS=1 and start the Docker stack to run live database checks.'
            );
        }
    }

    public function test_mariadb_connection()
    {
        config(['database.connections.mariadb.database' => 'tp_bd']);
        DB::connection('mariadb')->getPdo();

        $this->assertTrue(true);
    }

    public function test_mongodb_connection()
    {
        $connection = DB::connection('mongodb');
        $connection->getMongoClient()->listDatabases();

        $this->assertTrue(true);
    }

    public function test_redis_connection()
    {
        Redis::connection()->ping();

        $this->assertTrue(true);
    }
}
