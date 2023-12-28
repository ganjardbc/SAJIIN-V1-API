<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('toping_price')->default(0);
            $table->bigInteger('price')->default(0);
            $table->bigInteger('discount')->default(0);
            $table->integer('quantity')->default(0);
            $table->bigInteger('subtotal')->default(0);
            $table->string('product_image')->nullable();
            $table->string('product_name');
            $table->string('product_detail')->nullable();
            $table->string('product_toping')->nullable();
            $table->string('promo_code')->nullable();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('proddetail_id')->nullable();
            $table->unsignedBigInteger('toping_id')->nullable();
            $table->string('status')->nullable();
            $table->bigInteger('loki_index')->default(0); //yyyymmddhhmmss
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders');
            // $table->foreign('product_id')->references('id')->on('products');
            // $table->foreign('toping_id')->references('id')->on('topings');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}
