<?php

namespace App\Services;

use App\Models\CommanderLog;
use Dotenv\Util\Str;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use PhpParser\Node\Expr\Cast\Array_;

class BcService
{
    const brainCloudUrl = 'https://sharedprod.braincloudservers.com/s2sdispatcher';
    static private $SERVER_NAME = "DesignDataExportServer";
    static private $DEV_API_ID = "13804";
    static private $DEV_GAME_SECRET = "17b06270-05a5-4486-b081-b18a54c810e6";
    static private $TEST_API_ID = "13805";
    static private $TEST_GAME_SECRET = "17b06270-05a5-4486-b081-b18a54c810e6";
    static private $LIVE_API_ID = "";
    static private $LIVE_GAME_SECRET = "";

    static public function get_SESSION_LESS_STRUCTURE(string $version) {
        $API_ID = '';
        $GAME_SECRET = '';
        $SERVER_NAME = self::$SERVER_NAME;
        $low_version = strtolower($version);
        if ($low_version === 'dev') {
            $API_ID = self::$DEV_API_ID;
            $GAME_SECRET = self::$DEV_GAME_SECRET;
        } elseif ($low_version === 'test') {
            $API_ID = self::$TEST_API_ID;
            $GAME_SECRET = self::$TEST_GAME_SECRET;
        } elseif ($low_version === 'live') {
            $API_ID = self::$LIVE_API_ID;
            $GAME_SECRET = self::$LIVE_GAME_SECRET;
        }

        $json = <<<JSON
        {
            "appId": "$API_ID",
            "serverName": "$SERVER_NAME",
            "gameSecret": "$GAME_SECRET",
            "service": "script",
            "operation": "RUN",
            "data": {
                "scriptName": "",
                "scriptData": ""
            }
        }
        JSON;
        
        return json_decode($json, true);
    }

    static public function checkSuccess(Response $response)
    {
        $arr_res = $response->json();
        if (array_key_exists('success', $arr_res)) {
            if (!$arr_res['success'] || array_key_exists('reason_code', $arr_res)) { 
                abort(590, 'brainCloud_system_error: '.$arr_res['response']);
            }
        }
         
        if (array_key_exists('response', $arr_res)) { 
            $res = $arr_res['response'];
            if (is_array($res) && array_key_exists('success', $res) && $res['success'] === false) { 
                abort(591, 'brainCloud_throw_error: '.$res['error']);
            }
        }
    }

    static public function runScript(string $version, string $scriptName, array $params) {
   
        $json = self::get_SESSION_LESS_STRUCTURE($version);
        $json['data']['scriptName'] = $scriptName;
        $json['data']['scriptData'] = $params;
        $getResponse = Http::post(self::brainCloudUrl, $json);
  
        // !!check error
        BcService::checkSuccess($getResponse);

        return $getResponse;
    }

    static public function getEntityList(string $version, string $entityType) {
   
        $json = self::get_SESSION_LESS_STRUCTURE($version);
        $json['service'] = "customEntity";
        $json['operation'] = "SYS_GET_ENTITY_PAGE";
        if (!$entityType) return response([], 204);
        $json['data']['entityType'] = $entityType;
        $json['data']['context'] = [];
        $json['data']['context']['pagination'] = json_decode("{}");
        $getResponse = Http::post(self::brainCloudUrl, $json);

        $results = $getResponse['results'];
        $res = [
            'count' => $results['count'],
            'items' => $results['items'],
            'page' => $results['page'],
        ];
        foreach($res['items'] as $key => $value) {
            $res['items'][$key] = $value['data'];
        }
        return $res;
    }
    
    static public function searchEntityList(string $version,
                                            string $entityType, 
                                            array $searchMongoParam = null,
                                            int $page = 1,
                                            ?int $pageSize = null,
                                            array $sort = null) {

        $json = self::get_SESSION_LESS_STRUCTURE($version);
        $json['service'] = "customEntity";
        $json['operation'] = "SYS_GET_ENTITY_PAGE";
        if (!$entityType) return response([], 422);
        $json['data']['entityType'] = $entityType;
        $json['data']['context'] = [];
        $json['data']['context']['pagination'] = json_decode("{}");
        
        if($searchMongoParam)
            $json['data']['context']['searchCriteria'] = $searchMongoParam;
        
        // 檢查排序項是否為空
        if(!$sort) {
            $json['data']['context']['sortCriteria'] = json_decode("{}");
        } else {
            $json['data']['context']['sortCriteria'] = $sort;
        }

        // 是否要分頁
        if ($pageSize) {
            $json['data']['context']['pagination'] = [
                "pageNumber" => $page,
                "rowsPerPage" => $pageSize
            ];
        }
        //return $json;
        $getResponse = Http::post(self::brainCloudUrl, $json);


        $results = $getResponse['results'];
        $res = [
            'count' => $results['count'],
            'items' => $results['items'],
            'page' => $results['page'],
            'moreAfter' => $results['moreAfter'],
            'moreBefore' => $results['moreBefore'],
        ];
        foreach($res['items'] as $key => $value) {
            $res['items'][$key] = $value['data'];
        }
        return $res;
    }


    static public function runScheduleScript(Request $request, string $scriptName) {
        $version = $request->header('App-Version');
        $json = self::get_SESSION_LESS_STRUCTURE($version);
        $json['operation'] = 'SCHEDULE_CLOUD_SCRIPT';
        $json['data']['scriptName'] = $scriptName;
        $json['data']['scriptData'] = $request->all();
        if(!$request->has('startDateUTC')) abort(400, 'The param hasn\'t startDateInUTC');
        $json['data']['startDateUTC'] = $request->input("startDateUTC");

        $getResponse = Http::post(self::brainCloudUrl, $json);
        return $getResponse;
    }

    static public function runBatchScript(Request $request, string $scriptName, string $completionScriptName) {
        $version = $request->header('App-Version');
        $json = self::get_SESSION_LESS_STRUCTURE($version);
        $json['operation'] = 'RUN_BATCH_USER_SCRIPT';
        $json['data']['scriptName'] = $scriptName;
        $json['data']['scriptData'] = $request->all();
        $json['data']['completionScriptName'] = $completionScriptName;

        $getResponse = Http::post(self::brainCloudUrl, $json);
        return $getResponse;
    }

    static public function runScheduleBatchScript(Request $request, string $scriptName, string $completionScriptName) {
        $version = $request->header('App-Version');
        $json = self::get_SESSION_LESS_STRUCTURE($version);
        $json['operation'] = 'SCHEDULE_BATCH_USER_SCRIPT';
        $json['data']['scriptName'] = $scriptName;
        $json['data']['scriptData'] = $request->all();
        $json['data']['segmentIdList'] = [];
        $json['data']['completionScriptName'] = $completionScriptName;
        if(!$request->has('startDateUTC')) abort(400, 'The param hasn\'t startDateInUTC');
        $json['data']['startDateUTC'] = $request->input("startDateUTC");
        $getResponse = Http::post(self::brainCloudUrl, $json);
        return $getResponse;
    }

    static public function getScheduledScript(string $version) {
        $json = self::get_SESSION_LESS_STRUCTURE($version);
        $json['operation'] = 'GET_SCHEDULED_CLOUD_SCRIPTS';
        $json['data']['startDateUTC'] = 9999999999999; //不設限
        $getResponse = Http::post(self::brainCloudUrl, $json);
        return $getResponse;
    }

    static public function cancelScheduledScript(string $version, $jobId) {
        $json = self::get_SESSION_LESS_STRUCTURE($version);
        $json['operation'] = 'CANCEL_SCHEDULED_SCRIPT';
        $json['data']['jobId'] = $jobId;
        $getResponse = Http::post(self::brainCloudUrl, $json);
        return $getResponse;
    }


    static public function getGlobalEntitySingleton(string $version, string $entityType) {
        if (!$entityType) return response([], 422);

        $json = self::get_SESSION_LESS_STRUCTURE($version);
        $json['service'] = "globalEntity";
        $json['operation'] = "GET_PAGE";
        $json['data']['context'] = [];
        $json['data']['context']['searchCriteria'] = ['entityType' => $entityType];
        $getResponse = Http::post(self::brainCloudUrl, $json);

        $results = $getResponse['results'];
        $res = $results['items'][0]['data']; // 只提取第一個項目
        return $res;
    }

    static public function downloadDesignData(string $version,string $dataName) {

        // 確認資料是否有下載下來
        $filePath = 'DesignData/'.$dataName.'.json';
        // 如果本地有資料，確認資料是否為今天的，如果不是則更新下載資料
        if (Storage::exists($filePath)) {
            $updateDate = date('Y-m-d', Storage::lastModified($filePath));
            $today = date('Y-m-d');
            if ($today === $updateDate) {
                return json_decode(Storage::get($filePath));
            }
        }


        // get data from brainCloud
        $items = [];
        $page = 1;
        $contents = BcService::searchEntityList($version, $dataName, null, $page, 100);
        $items = array_merge($items, $contents['items']);
        
        while ($contents['moreBefore']) {
            $page += 1;
            $getRes = BcService::searchEntityList($version, $dataName, null, $page, 100);
            $items = array_merge($items, $contents['items']);
        }

        Storage::put($filePath, json_encode($items));
        $items = json_decode(Storage::get($filePath));
        return $items;
    }
}
