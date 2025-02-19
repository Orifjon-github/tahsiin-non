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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('name_ru')->nullable();
            $table->string('name_en')->nullable();
            $table->string('info')->nullable();
            $table->string('info_ru')->nullable();
            $table->string('info_en')->nullable();
            $table->enum('enable', ['0', '1'])->default('1');
            $table->timestamps();
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('consultations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
