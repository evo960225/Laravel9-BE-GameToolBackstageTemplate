<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role; 
use App\Models\User;

class CreateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('roles');
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('role_id')->unique();
            $table->string('name')->unique();
            $table->string('ch_name');
            $table->timestamps();
        });

        
        //User::find(1)->
        Role::create([
            'role_id' => 0,
            'name' => 'admin',
            'ch_name' => '管理員']);

        Role::create([
            'role_id' => 1,
            'name' => 'dev', 
            'ch_name' => '開發人員']);

        Role::create([
            'role_id' => 2, 
            'name' => 'gm', 
            'ch_name' => 'GM']);

        Role::create([
            'role_id' => 99,
            'name' => 'member', 
            'ch_name' => '一般']);


        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('role_id')->default(99);
            $table->foreign('role_id')
                ->references('role_id')
                ->on('roles')
                ->onDelete('cascade');
        }); 
        User::where('name','admin')->update(['role_id' => 0]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_role_id_foreign');
            $table->dropColumn('role_id');
        });
        Schema::dropIfExists('roles');
    }
}
