<?php

use App\Database\LegacyMigrations\CreateRollovers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        (new CreateRollovers())->up(DB::connection()->getPdo());
    }

    public function down(): void
    {
        (new CreateRollovers())->down(DB::connection()->getPdo());
    }
};