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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 100);
            $table->string('email', 50);
            $table->string('address_one', 100);
            $table->string('address_two', 100);
            $table->string('shipping_address_check', 20);
            $table->string('shipping_address', 200)->nullable();

            $table->decimal('amount', 10);
            $table->enum('status', array('Progress','Complete'))->default('Progress');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
