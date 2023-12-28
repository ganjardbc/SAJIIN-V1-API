<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reference_id')->nullable(); 
            $table->string('discount_image')->nullable();
            $table->string('discount_name')->nullable();
            $table->string('discount_description')->nullable();
            $table->string('discount_type')->nullable();
            $table->string('discount_value_type')->nullable();
            $table->double('discount_value')->nullable();
            $table->bigInteger('discount_fee')->nullable();
            $table->bigInteger('discount_price')->nullable(); //after discount
            $table->bigInteger('product_price')->nullable(); //before discount
            $table->unsignedBigInteger('discount_configuration_id')->nullable();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('discount_id')->nullable();
            $table->bigInteger('loki_index')->default(0); //yyyymmddhhmmss
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_discounts');
    }
}
