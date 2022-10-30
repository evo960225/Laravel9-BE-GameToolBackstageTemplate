<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommanderLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('commander_logs');
        Schema::create('commander_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commander_id');
            $table->foreign('commander_id')->references('id')->on('users');
            $table->string('target_id_type');
            $table->string('target_id');
            $table->string('operation');
            $table->text('param')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commander_logs');
    }
}
