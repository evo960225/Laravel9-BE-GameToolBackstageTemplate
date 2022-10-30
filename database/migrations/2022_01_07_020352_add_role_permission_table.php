<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role; 
use App\Models\RolePermission; 

class AddRolePermissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('role_permissions');
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('role_id');

            $table->foreign('role_id')
                ->references('role_id')
                ->on('roles');
            $table->unsignedInteger('permission_id');
            $table->timestamps(); 
        });
        
        $roles = Role::all();
        foreach ($roles as $role) {
            if ($role->name === 'admin') continue;
            for ($i=100; $i<400; $i++) {
                $user = RolePermission::create([
                    'role_id' => $role->role_id,
                    'permission_id' => $i
                ]);
            }
        }
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_permissions');
    }
}
