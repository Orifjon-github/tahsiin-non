<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('language', 2)->default('uz'); // uz, ru
            $table->string('step', 50)->default('start');
            $table->string('building_number', 10)->nullable(); // Uy raqami
            $table->string('apartment_number', 10)->nullable(); // Xonadon raqami
            $table->string('temp_address')->nullable(); // Vaqtinchalik manzil
            $table->string('address')->nullable(); // Tasdiqlangan manzil

            $table->integer('consultation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();

            $table->index('chat_id');
            $table->index('phone');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
