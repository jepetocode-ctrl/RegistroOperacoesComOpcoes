<?php

use App\Database\LegacyMigrations\CreateStructuredStrategyDomain;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        (new CreateStructuredStrategyDomain())->up(DB::connection()->getPdo());
    }

    public function down(): void
    {
        (new CreateStructuredStrategyDomain())->down(DB::connection()->getPdo());
    }
};
