<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CurrentUserController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BC\BcMailController;
use App\Http\Controllers\GameApiExtendController;
use App\Http\Controllers\GameFinishLevelController;
use App\Http\Controllers\GamePlayerController;
use App\Http\Controllers\GameStatisticsController;
use App\Scheduling;


Route::get('/', function() { echo "x______x"; });
Route::get('/check-state', [AuthController::class, 'checkState']); //確認權限、登入狀態

Route::middleware(['auth:sanctum'])->group(function() {
    
    # User profile
    # |- 2fa
    Route::post ('/enable-2fa',      [AuthController::class, 'enable2fa']);
    Route::post ('/disable-2fa',     [AuthController::class, 'disable2fa']);
    Route::get  ('/get-2fa-qr',      [AuthController::class, 'get2faQrCode']);
    # |- account
    Route::get  ('/me',              [CurrentUserController::class, 'show']);
    Route::patch('/me',              [CurrentUserController::class, 'update']);
    Route::patch ('/reset-password', [AuthController::class, 'resetPassword']);

    # Model controller
    # |- user
    Route::get  ('/user',           [UserController::class, 'index'])                       ->middleware(['permission:402']);
    Route::post ('/register',       [AuthController::class, 'register'])                    ->middleware(['permission:401']);
    Route::get  ('/user/{user}',    [UserController::class, 'show'])                        ->middleware(['permission:402']);
    Route::patch('/user-role',      [UserController::class, 'changeUserRole'])              ->middleware(['permission:402']);
    Route::delete('/user/{user}',   [UserController::class, 'destroy'])                     ->middleware(['permission:402']);
    # |- role
    Route::get  ('/role',           [RoleController::class, 'index']); 
    Route::get  ('/role/{role}',    [RoleController::class, 'show'])                        ->middleware(['permission:403']); 
    Route::put  ('/role/{role}',    [RoleController::class, 'update'])                      ->middleware(['permission:403']); 

    # search user data
    Route::get  ('/game/accounts',       [GamePlayerController::class, 'getUserList']);
    Route::get  ('/game/user-data',      [GamePlayerController::class, 'getUserData']);
    Route::get  ('/game/item-list',      [GamePlayerController::class, 'getItemList']);
    
    Route::get  ('game/announcement',    [GamePlayerController::class, 'getAnnouncementList']);
    Route::post ('game/update-login-announcement',    [GamePlayerController::class, 'updateAnnouncementList']);
    Route::get  ('game/freezed-player',  [GamePlayerController::class, 'getFreezedPlayer']);
    Route::get  ('game/freezed-ip',      [GamePlayerController::class, 'getFreezedIp']);
    Route::post ('game/freeze-player',  [GamePlayerController::class, 'freezePlayer']);
    Route::post ('game/freezed-ip',      [GamePlayerController::class, 'updateFreezedIp']);

    Route::post ('game/schedule-announcement',    [GameApiExtendController::class, 'scheduleBroadcast']);
    Route::get  ('game/schedule-announcement',    [GameApiExtendController::class, 'getScheduleBroadcast']);
    Route::delete('game/schedule-announcement',   [GameApiExtendController::class, 'deleteScheduleBroadcast']);
    


    // opearate
    Route::post ('/game/operate/send-mail',             [BcMailController::class, 'sendMail']);//   ->middleware(['permission:210']); @TODO: permission;
 

});
