<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('strategy_legs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('strategy_id');
            $table->foreign('strategy_id')->references('id')->on('strategies')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->unsignedInteger('option_contract_id')->nullable();
            $table->foreign('option_contract_id')->references('id')->on('option_contracts')->restrictOnDelete();
            $table->string('instrument_type', 20)->default('OPTION');
            $table->string('option_type', 4);
            $table->decimal('strike_price', 18, 6);
            $table->date('expiration_date');
            $table->string('side', 4);
            $table->unsignedInteger('quantity');
            $table->decimal('premium', 18, 6);
            $table->unsignedInteger('contract_multiplier')->default(100);
            $table->decimal('costs', 18, 6)->default(0);
            $table->timestamps();
            $table->unique(['strategy_id', 'position']);
            $table->index(['strategy_id', 'expiration_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_legs');
    }
};