<?php

use App\Support\Keepalive;
use App\Support\PublicTableSecurity;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('db:enable-rls', function (): int {
    app(PublicTableSecurity::class)->enable();
    $this->info('RLS habilitada en public.');

    return 0;
});

Artisan::command('supabase:keepalive', function (): int {
    try {
        $count = app(Keepalive::class)->maintain();
        $this->info("Productos activos: {$count}");

        return 0;
    } catch (Throwable $exception) {
        Log::error('Falló el keep-alive de PostgreSQL.', ['type' => $exception::class]);
        $this->error('Falló el keep-alive de PostgreSQL.');

        return 1;
    }
});

Schedule::command('supabase:keepalive')->everySixHours()->withoutOverlapping()->onOneServer();

Schedule::command('orders:release-expired')->everyFiveMinutes()->withoutOverlapping()->onOneServer();
