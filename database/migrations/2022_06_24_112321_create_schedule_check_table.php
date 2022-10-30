<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduleCheckTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schedule_check', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('work_type');
            $table->string('datetime_type');
            $table->string('datetime_string');
            $table->boolean('is_worked');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('schedule_check');
    }
}
