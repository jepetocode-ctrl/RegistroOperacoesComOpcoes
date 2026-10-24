<?php

use App\Database\LegacyMigrations\AddDashboardArchiveFields;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        (new AddDashboardArchiveFields())->up(DB::connection()->getPdo());
    }

    public function down(): void
    {
        (new AddDashboardArchiveFields())->down(DB::connection()->getPdo());
    }
};
