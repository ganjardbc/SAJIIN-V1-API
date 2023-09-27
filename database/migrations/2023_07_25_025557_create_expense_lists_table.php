<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpenseListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expense_lists', function (Blueprint $table) {
            $table->id();
            $table->string('expense_list_id')->unique();
            $table->string('image')->nullable();
            $table->string('description')->nullable();
            $table->bigInteger('expense_price');
            $table->date('expense_date');
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('expense_type_id');
            $table->unsignedBigInteger('cashbook_id')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops');
            $table->foreign('expense_type_id')->references('id')->on('expense_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('expense_lists');
    }
}
