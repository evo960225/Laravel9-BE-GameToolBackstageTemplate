<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AwsDynamo;
use App\Services\BcPlayer;
use App\Services\BcService;
use Faker\Core\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GamePlayerController extends Controller
{
    private function validateHasPlayerIdentify(Request $request) {
        return $request->validate([
            'playerName' => 'required_without_all:bcId,supportCode',
            'bcId' => 'required_without_all:playerName,supportCode',
            'supportCode' => 'required_without_all:playerName,bcId',
        ]);
    }
    
    private function getBcId(string $version, array $params) {
        $bcId = null;
        if (array_key_exists('playerName' , $params)) {
            $bcId = BcPlayer::getPlayerIdByName($version, $params['playerName']);
        } else if (array_key_exists('supportCode' , $params)) {
            $bcId = BcPlayer::getPlayerIdByName($version, $params['supportCode']);
        } else {
            $bcId = $params['bcId'];
        }
        return $bcId;
    }

    private function getEmptyResult() {
        return ['item' => [], 'count' => 0];
    }


    public function getUserList(Request $request) {
        
        if ($request['id'] || $request['name']) {
            $users = DB::connection('mongodb')->table('gameUsers')
                ->where('玩家ID', intval($request['id']))
                ->orWhere('玩家名稱', $request['name'])
                ->get();
        } else {
            $users = DB::connection('mongodb')->table('gameUsers')->get();
        }

        return $users;
    }

    public function getUserData(Request $request) {
        $id = intval($request['id']);
        $name =  $request['name'];

        $user = DB::connection('mongodb')->table('gameUsers')
            ->where('玩家ID', $id)
            ->orWhere('玩家名稱', $name)
            ->first();
        unset($user['_id']);

        $userData = DB::connection('mongodb')->table('ex_gameUserData')
            ->where('玩家ID', $id)
            ->orWhere('玩家名稱', $name)
            ->first();
        unset($userData['_id']);

        return [
            'account' => $user,
            'data' => $userData
        ];
    }

    public function getItemList(Request $request) {
        $items = DB::connection('mongodb')->table('items')
            ->get();
        return $items;
    }

    public function getAnnouncementList(Request $request) {
        $everyTime = DB::connection('mongodb')->table('announcement')
            ->where('type', 'everyTime')->first();
        $once = DB::connection('mongodb')->table('announcement')
            ->where('type', 'once')->first();
        $period = DB::connection('mongodb')->table('announcement')
            ->where('type', 'period')->first();
        return [
            'everyTime' => $everyTime,
            'once' => $once,
            'period' => $period,
        ];
    }

    public function updateAnnouncementList(Request $request) {
        $announcementType = $request['announcementType'];
        $content = $request['content'];

        $everyTime = DB::connection('mongodb')->table('announcement')
            ->where('type', $announcementType)
            ->update(['content' => $content]);

        if ($announcementType) {
            $everyTime = DB::connection('mongodb')->table('announcement')
                ->where('type', $announcementType)
                ->update(['startTime' => $request['timeStart'], 'endTime' => $request['timeEnd']]); 
        }
          
        return [];
    }

    public function getFreezedPlayer(Request $request) {
        $items = DB::connection('mongodb')->table('freezePlayer')
            ->get();
        return [
            'items' => $items,
            'count' => count($items)
        ];
    }

    public function getFreezedIp(Request $request) {
        $items = DB::connection('mongodb')->table('freezeIp')
            ->get();
        return [
            'items' => $items,
            'count' => count($items)
        ];
    }
    public function freezePlayer(Request $request) {
        return 0;
    }
    
}
