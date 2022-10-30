<?php

namespace App\Temp;

use App\Http\Controllers\GameStatisticsController;
use App\Services\AwsDynamo;
use AWS;
use Aws\DynamoDb\Marshaler;
use Exception;


class DynamoDbFix 
{

    static public function fixLogDateColumn() {
        $version = 'dev';
        $tableName = AwsDynamo::getVersionTable($version, 'PlayerLog_FinishLevel');
        $options = [
            "TableName" => $tableName,
            "ExpressionAttributeNames" => [
                "#d" => "date"
            ],
            "FilterExpression" => "attribute_not_exists(#d)", 
            "ReturnConsumedCapacity" => "TOTAL" 
        ]; 
          
        $results = AwsDynamo::scanAllItems($options);
        
        $items = $results['items'];
        foreach ($items as &$item) {
            $item['date'] = substr($item['tk_datetimeIso'], 0 ,10);
        }
        return AwsDynamo::BatchWriteItem($tableName, $items);
    }   


    static public function calcAllFinishLevel() {
        $time1 = time();
        $startDate = '20220413';
        $endDate = '20220915';
        
            $processDate = $startDate;
            while ($processDate <= $endDate) {
                try {
                    GameStatisticsController::calcLevelFinishByDate($processDate);
                } catch(Exception $e) {
                    echo $e;
                }
                $processDate = date("Ymd", strtotime($processDate.' +1 day'));
            }
        
     

        echo time() - $time1;
    }
}
