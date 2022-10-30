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

class BcPlayer
{
    
    static public function getPlayerIdByName(string $version, string $name) {
        $id = BcService::runScript(
            $version, 
            'operations/S2S_GetPlayerIdByName', 
            ['name' => $name]
        )["response"];
        return $id;
    }

    static public function getPlayerIdBySupportCode(string $version, string $supportCode) {
        $id = BcService::runScript(
            $version, 
            'operations/S2S_GetPlayerIdBySupportCode', 
            ['supportCode' => $supportCode]
        )["response"];
        return $id;
    }


}
