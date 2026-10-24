<?php

use App\Database\LegacyMigrations\AddUserPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        (new AddUserPermissions())->up(DB::connection()->getPdo());
    }

    public function down(): void
    {
        (new AddUserPermissions())->down(DB::connection()->getPdo());
    }
};