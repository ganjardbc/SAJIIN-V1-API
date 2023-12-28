<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnerConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partner_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('partconfig_id')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('promo_code')->unique();
            $table->date('expire_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->unsignedBigInteger('partner_id');
            $table->bigInteger('loki_index')->default(0); //yyyymmddhhmmss
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('partner_id')->references('id')->on('partners');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partner_configurations');
    }
}
