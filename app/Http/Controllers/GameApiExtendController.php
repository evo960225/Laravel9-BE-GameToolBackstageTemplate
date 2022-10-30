<?php

namespace App\Http\Controllers;

use App\Functions\GameHost;
use App\Functions\HuaweiObsApi;
use App\Http\Controllers;
use App\Http\Controllers\GameApiController as ControllersGameApiController;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Mockery\Expectation;

class GameApiExtendController extends Controller
{
    // 大聲公排程 ------
    public function scheduleBroadcast(Request $request) {
        $data = $request->all();
        $result = DB::table('announcement_schedule')->insert([
            'serverId' => $data['serverId'],
            'startTimeString' => $data['startTime'],
            'endTimeString' => $data['endTime'],
            'cronString' => $data['frequency'],
            'announceContent' => $data['content'],
            'description' => $data['description'],
            'created_at'=> new \DateTime(),
            'updated_at'=> new \DateTime(),
        ]);
        
        return ['message' => $result ? 'OK' : 'Error'];
    }
    
    public function getScheduleBroadcast(Request $request) {
        
        $pageSize = $request->has('pageSize') ? $request['pageSize'] : 25;
        $data = $request->all();
        $result = DB::table('announcement_schedule')
            ->select('*')
            ->paginate($pageSize);
        
        return $result;
    }
    
    public function deleteScheduleBroadcast(Request $request) {
        
        $result = DB::table('announcement_schedule')
        
            ->delete($request['id']);
        return ['message' => $result ? 'OK' : 'Error'];
    }



}
