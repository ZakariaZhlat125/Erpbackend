<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('symbol', 20);
            $table->boolean('is_base')->default(false);
            $table->timestamps();

            $table->index(['organization_id', 'is_base']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
