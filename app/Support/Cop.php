<?php

namespace App\Support;

final class Cop
{
    public static function format(int|string $amount): string
    {
        return '$'.preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', (string) $amount).' COP';
    }
}
