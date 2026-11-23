<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PublicTableSecurity
{
    public function enable(): void
    {
        DB::transaction(function (): void {
            $tables = DB::select(<<<'SQL'
                SELECT c.relname, pg_get_userbyid(c.relowner) = current_user AS owned,
                       c.relrowsecurity, c.relforcerowsecurity
                FROM pg_class AS c
                JOIN pg_namespace AS n ON n.oid = c.relnamespace
                WHERE n.nspname = 'public' AND c.relkind IN ('r', 'p')
                ORDER BY c.relname
                SQL);

            foreach ($tables as $table) {
                if (! $table->owned || $table->relforcerowsecurity) {
                    throw new RuntimeException('Verifica el rol propietario y la configuración de RLS en public.');
                }

                if (! $table->relrowsecurity) {
                    $identifier = '"'.str_replace('"', '""', $table->relname).'"';
                    DB::statement("ALTER TABLE public.{$identifier} ENABLE ROW LEVEL SECURITY");
                }
            }

            if (DB::table('pg_policies')->where('schemaname', 'public')->exists()) {
                throw new RuntimeException('El esquema public dedicado debe estar libre de políticas de acceso.');
            }
        });
    }
}
