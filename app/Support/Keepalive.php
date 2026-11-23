<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

final class Keepalive
{
    public function ping(string $source): void
    {
        DB::table('keepalive_pings')->insert(['source' => $source, 'pinged_at' => now()]);
    }

    public function maintain(): int
    {
        return DB::transaction(function (): int {
            $this->ping('scheduler');
            DB::table('keepalive_pings')->where('pinged_at', '<', now()->subDays(14))->delete();

            return Product::query()->activo()->count();
        });
    }
}
