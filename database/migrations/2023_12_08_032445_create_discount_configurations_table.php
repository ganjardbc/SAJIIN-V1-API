<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscountConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('discount_id')->unique();
            $table->string('discount_image')->nullable();
            $table->string('discount_name');
            $table->string('discount_description')->nullable();
            $table->enum('discount_type', ['transaction', 'product'])->nullable();
            $table->enum('discount_value_type', ['percentage', 'nominal'])->nullable();
            $table->double('discount_value');
            $table->boolean('is_public')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->unsignedBigInteger('shop_id');
            $table->bigInteger('loki_index')->default(0); //yyyymmddhhmmss
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discounts');
    }
}
