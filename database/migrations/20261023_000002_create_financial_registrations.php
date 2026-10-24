<?php

use App\Database\LegacyMigrations\CreateFinancialRegistrations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        (new CreateFinancialRegistrations())->up(DB::connection()->getPdo());
    }

    public function down(): void
    {
        (new CreateFinancialRegistrations())->down(DB::connection()->getPdo());
    }
};