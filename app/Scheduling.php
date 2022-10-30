<?php

namespace App;

use AWS;
use App\Services\AwsDynamo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class Scheduling
{
    static public function getCsvFilePath() {
        return storage_path().'/game-logs/';
    } 

    static public function getTableSchema($appVersion) {
        return 'TWMK-'.$appVersion;
    } 

    static private function getLogData($appVersion, $logType, $date) {
        $datetimeStart = strtotime($date.'000000') * 1000;
        $datetimeEnd = strtotime($date.'235959') * 1000 + 999;

        # request 
        $client = AWS::createDynamoDb(['region' => 'us-east-1']);

        $itemList = [];
        $lastEvaluatedKey = null; // Dynamo的分頁key
        do {
            $options = [
                'ConsistentRead' => false,
                'TableName' => AwsDynamo::getVersionTable($appVersion, 'GameLog'),
                'KeyConditionExpression' => 
                    '#type = :type and (#timeStamp BETWEEN :timeStart AND :timeEnd)',
                "ExpressionAttributeNames" => [
                    '#type' => 'type',
                    '#timeStamp'   => 'timeStamp'
                ],
                'ExpressionAttributeValues' => [
                    ":type"         => ["S" => $logType],
                    ":timeStart"    => ["N" => strval($datetimeStart)],
                    ":timeEnd"      => ["N" => strval($datetimeEnd)],
                ],
                'ReturnConsumedCapacity'    => 'TOTAL',
                'Limit' => 10000,
            ];
            if ($lastEvaluatedKey) {
                $options['ExclusiveStartKey'] = $lastEvaluatedKey;
            }
            $result = $client->query($options);
            $itemList = array_merge($itemList,  AwsDynamo::unmarshalerList($result['Items']) ?? []);
            $lastEvaluatedKey = $result['LastEvaluatedKey'] ?? null;
        } while ($lastEvaluatedKey);

        return $itemList;
    }

    static private function phpTypeToMysqlType($phpType)
    {
        if ($phpType === 'integer') {
            return 'bigint';
        } else if ($phpType === 'double') {
            return 'double';
        } else if ($phpType === 'string') {
            return 'text';
        }
        return 'text'; 
    }

    static public function getGameLogToCsv($appVersion, $logType, $date)
    {
        $spendTime = -microtime(true);

        $itemList = self::getLogData($appVersion, $logType, $date);
        $tableSchema = self::getTableSchema($appVersion);

        $logDataTypes = [];
        $csvData = [];
        // 尋找項目所有欄位名稱
        $commonColumns = ['type','sequenceno','serviceid','createdate','accountno','accountname','worldid','accounttype','playertype'];
        $columns = [];
        foreach ($itemList as $index => $value) {
            $csvData[$index] = [];
            $value['serviceid'] = $tableSchema;

            // 照共同格式順序放
            foreach ($commonColumns as $colName) {
                $csvData[$index][$colName] = $value[$colName];
            }

            // 資料壓扁
            foreach ($value['logData'] as $colName => $colValue) {
                if (is_array($colValue)) {
                    $csvData[$index][$colName] = json_encode($colValue);
                    $logDataTypes[$colName] = 'text';
                } else {
                    $csvData[$index][$colName] = $colValue;
                    $sqlType = self::phpTypeToMysqlType(gettype($colValue));
                    $logDataTypes[$colName] = $sqlType;
                }
            }

            $columns = array_unique($columns + array_keys($csvData[$index]));
        }

        $date_ymd = date('Ymd', strtotime($date));
        
        Scheduling::writeDateTableSchema($tableSchema, $logType, $date);
        Scheduling::writeDateColumnSchema($tableSchema, $logType, $date, $logDataTypes);


        // ----------------------------------
        $dir = self::getCsvFilePath();
        if( !is_dir($dir) ) {
            mkdir($dir);
        }
        $fp = fopen($dir.$tableSchema.'.'.$logType.'.'.$date.'.csv', 'w');

        // 為了使逗號數量固定，建立固定長度array當模板
        $template = array_fill_keys($columns, '');
        
        foreach ($csvData as $row) {
            fputs($fp, implode(',', $row + $template)."\n");
        }
        fclose($fp);

        $spendTime += microtime(true);
        return ['spendTime' => $spendTime];
    }

    static public function writeDateTableSchema($tableSchema, $logType, $calcDate_ymd)
    {
        // 確認app version 資料夾有沒有存在，沒有則新建
        $dir = self::getCsvFilePath();
        if (!is_dir($dir)) mkdir($dir);

        // 確認該日csv檔有沒有存在，沒有則新建
        $fileName = $tableSchema.'.tables.'.$calcDate_ymd.'.csv';
        if (!is_file($dir.$fileName)) {
            copy(storage_path().'/csv_template/table_schema.csv', $dir.$fileName);
        } else {
            // 刪除原有的table name紀錄，為了不重複資料
            $TABLE_NAME_INDEX = 2;
            $fpw = fopen($dir.$fileName.'.tmp', "w");
            if (($fpr = fopen($dir.$fileName, "r")) !== FALSE) {
                while (($row = fgetcsv($fpr, 1000, "\t")) !== FALSE) {
                    if ($row[$TABLE_NAME_INDEX] === $logType) {
                        continue;
                    }
                    fputcsv($fpw,$row);
                }
                fclose($fpr);
            }
            fclose($fpw);
            rename($dir.$fileName.'.tmp', $dir.$fileName);
        }

        // write file
        $fp = fopen($dir.$fileName, 'a');

        // 寫內容
        $baseContent = [
            'TABLE_CATALOG' => 'def', 
            'TABLE_SCHEMA' => $tableSchema,
            'TABLE_NAME' => $logType,
            'TABLE_TYPE' => 'BASE TABLE'
        ];

        fputs($fp, implode("\t", $baseContent)."\n");
        fclose($fp);
    }

    static public function writeDateColumnSchema($tableSchema, $logType, $calcDate_ymd, $columnsToTypes)
    {
        // 確認app version 資料夾有沒有存在，沒有則新建
        $dir = self::getCsvFilePath();
        if (!is_dir($dir)) mkdir($dir);

        // 確認該日csv檔有沒有存在，沒有則新建
        $fileName = $tableSchema.'.columns.'.$calcDate_ymd.'.csv';
        if (!is_file($dir.$fileName)) {
            copy(storage_path().'/csv_template/column_schema.csv', $dir.$fileName);
        } else {
            // 刪除原有的table name紀錄，為了不重複資料
            $TABLE_NAME_INDEX = 2;
            $fpw = fopen($dir.$fileName.'.tmp', "w");
            if (($fpr = fopen($dir.$fileName, "r")) !== FALSE) {
                while (($row = fgetcsv($fpr, 1000, "\t")) !== FALSE) {
                    if ($row[$TABLE_NAME_INDEX] === $logType) {
                        continue;
                    }
                    fputcsv($fpw,$row);
                }
                fclose($fpr);
            }
            fclose($fpw);
            rename($dir.$fileName.'.tmp', $dir.$fileName);
        }
        

        $fp = fopen($dir.$fileName, 'a');

        // 寫共同定義內容
        $baseContent = [
            ['def', $tableSchema, $logType, 'type', 1, 'text', 'NULL', 'NULL'], 
            ['def', $tableSchema, $logType, 'sequenceno', 2, 'bigint', 'NULL', 'NULL'], 
            ['def', $tableSchema, $logType, 'serviceid', 3, 'text', 'NULL', 'NULL'], 
            ['def', $tableSchema, $logType, 'createdate', 4, 'timestamp', 'NULL', 'NULL'], 
            ['def', $tableSchema, $logType, 'accountno', 5, 'text', 'NULL', 'NULL'], 
            ['def', $tableSchema, $logType, 'accountname', 6, 'text', 'NULL', 'NULL'], 
            ['def', $tableSchema, $logType, 'worldid', 7, 'text', 'NULL', 'NULL'], 
            ['def', $tableSchema, $logType, 'accounttype', 8, 'text', 'NULL', 'NULL'], 
            ['def', $tableSchema, $logType, 'playertype', 9, 'text', 'NULL', 'NULL']
        ];   
        foreach ($baseContent as $row) {
            fputcsv($fp, $row, "\t");
        }

        // 寫入logData內容
        $rowIndex = 10;
        foreach ($columnsToTypes as $columnName => $columnType) {
            $row =  ['def', $tableSchema, $logType, $columnName, $rowIndex, $columnType, 'NULL', 'NULL'];
            $rowIndex++;
            fputs($fp, implode("\t", $row)."\n");
        }

        fclose($fp);
    }

    static public function exportFinish($tableSchema, $calcDate_ymd)
    {
        $dir = self::getCsvFilePath();
        $fileName = $tableSchema.'.'.$calcDate_ymd.'.finish';

        if (!is_dir($dir)) mkdir($dir);
        $fp = fopen($dir.$fileName, 'w');
        fclose($fp);
    }
}