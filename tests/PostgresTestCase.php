<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use RuntimeException;

abstract class PostgresTestCase extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as private migrateTestDatabase;
    }

    public function runDatabaseMigrations(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('Configura PostgreSQL de pruebas según el README.');
        }

        $database = config('database.connections.pgsql.database');
        if (! str_ends_with($database, '_test') || config('database.connections.pgsql.url')) {
            throw new RuntimeException('Configura una base de datos aislada con sufijo _test y DB_URL vacío.');
        }

        $this->migrateTestDatabase();
    }
}
