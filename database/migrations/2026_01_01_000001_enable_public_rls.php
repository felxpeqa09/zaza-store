<?php

use App\Support\PublicTableSecurity;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PublicTableSecurity::class)->enable();
    }

    public function down(): void
    {
        // Preserve RLS on public tables that survive a rollback.
    }
};
