<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlatformsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('platforms', function (Blueprint $table) {
            $table->id();
            $table->string('platform_id')->unique();
            $table->string('image')->nullable();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('currency_type');
            $table->bigInteger('order_fee');
            $table->string('order_type');
            $table->boolean('is_available');
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
        Schema::dropIfExists('platforms');
    }
}
