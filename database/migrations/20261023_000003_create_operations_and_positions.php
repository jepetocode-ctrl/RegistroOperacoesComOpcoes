<?php

use App\Database\LegacyMigrations\CreateOperationsAndPositions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        (new CreateOperationsAndPositions())->up(DB::connection()->getPdo());
    }

    public function down(): void
    {
        (new CreateOperationsAndPositions())->down(DB::connection()->getPdo());
    }
};