<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    public function test_mariadb_connection()
    {
        try {
            config(['database.connections.mariadb.database' => 'tp_bd']);
            DB::connection('mariadb')->getPdo();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Failed to connect to MariaDB: ' . $e->getMessage());
        }
    }

    public function test_mongodb_connection()
    {
        try {
            $connection = DB::connection('mongodb');
            $connection->getMongoClient()->listDatabases();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Failed to connect to MongoDB: ' . $e->getMessage());
        }
    }

    public function test_redis_connection()
    {
        try {
            Redis::connection()->ping();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Failed to connect to Redis: ' . $e->getMessage());
        }
    }
}
