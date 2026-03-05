<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (Schema::hasTable('conversations')) {
            DB::statement('ALTER TABLE conversations ALTER COLUMN metadata TYPE jsonb USING metadata::jsonb');
        }

        if (Schema::hasTable('messages')) {
            DB::statement('ALTER TABLE messages ALTER COLUMN tool_payload TYPE jsonb USING tool_payload::jsonb');
            DB::statement('ALTER TABLE messages ALTER COLUMN token_usage TYPE jsonb USING token_usage::jsonb');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (Schema::hasTable('conversations')) {
            DB::statement('ALTER TABLE conversations ALTER COLUMN metadata TYPE json USING metadata::json');
        }

        if (Schema::hasTable('messages')) {
            DB::statement('ALTER TABLE messages ALTER COLUMN tool_payload TYPE json USING tool_payload::json');
            DB::statement('ALTER TABLE messages ALTER COLUMN token_usage TYPE json USING token_usage::json');
        }
    }
};
