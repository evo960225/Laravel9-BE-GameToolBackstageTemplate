<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnnouncementScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('announcement_schedule', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('serverId');

            $table->string('startTimeString');
            $table->string('endTimeString');
            $table->string('cronString');
            $table->string('announceContent');
            $table->string('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('announcement_schedule');
    }
}
