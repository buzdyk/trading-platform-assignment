<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('symbol_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 18, 8)->default(0);
            $table->decimal('locked_amount', 18, 8)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'symbol_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
