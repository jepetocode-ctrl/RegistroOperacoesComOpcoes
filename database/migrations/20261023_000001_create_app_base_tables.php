<?php

use App\Database\LegacyMigrations\CreateAppBaseTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        (new CreateAppBaseTables())->up(DB::connection()->getPdo());
    }

    public function down(): void
    {
        (new CreateAppBaseTables())->down(DB::connection()->getPdo());
    }
};