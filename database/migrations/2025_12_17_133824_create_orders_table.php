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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Buyurtma ma'lumotlari
            $table->string('order_number')->unique()->nullable(); // TB-0001
            $table->integer('quantity')->default(1); // Non soni
            $table->decimal('price_per_item', 10, 2)->default(3500);
            $table->decimal('total_price', 10, 2);

            // Yetkazish
            $table->date('delivery_date')->nullable();
            $table->string('delivery_time_slot', 20)->nullable(); // 7:00-7:30

            // Holat
            $table->enum('status', [
                'pending',      // Yaratilmoqda
                'confirmed',    // Tasdiqlangan
                'completed',    // Bajarilgan
                'cancelled'     // Bekor qilingan
            ])->default('pending');

            // Vaqt belgilari
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('order_number');
            $table->index('delivery_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
