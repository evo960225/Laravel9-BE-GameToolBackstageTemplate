<?php

namespace App\Services;

use AWS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use Aws\DynamoDb\Marshaler;
use Exception;

class AwsDynamo
{

    public static function unmarshalerList(array $itemList) {
        $marshaler = new Marshaler();
        $data = [];
        foreach($itemList as $item)
        {
            $data[] = $marshaler->unmarshalItem($item);
        }
        return $data;
    }

    // [['test'=>'123'],['test'=>'123']] =>
    //  => [{"test":{"S":"123"}},{"test":{"S":"123"}}]
    public static function marshalerList(array $arrayList) {
        $marshaler = new Marshaler();
        $data = [];
        foreach($arrayList as $item)
        {
            $data[] = $marshaler->marshalItem($item);
        }
        return $data;
    }

    public static function marshalerPutItemList(array $arrayList) {
        $marshaler = new Marshaler();
        $data = [];
        foreach($arrayList as $item)
        {
            $data[] = ['PutRequest' => 
                        ['Item' => $marshaler->marshalItem($item)]
                      ];
        }
        return $data;
    }

    public static function getVersionTable(string $version,string $tableName) {
        return ucfirst($version) . '_' . $tableName;
    }

    static public function queryAllItems($options) {
        $client = AWS::createDynamoDb();
        $itemList = [];
        $itemCount = 0;
        $lastEvaluatedKey = null; // Dynamo的分頁key
        do {
            if ($lastEvaluatedKey) {
                $options['ExclusiveStartKey'] = $lastEvaluatedKey;
            }
            $result = $client->query($options);
            $itemList = array_merge($itemList,  AwsDynamo::unmarshalerList($result['Items']) ?? []);
            $itemCount +=  $result['Count'];
            $lastEvaluatedKey = $result['LastEvaluatedKey'] ?? null;
        } while ($lastEvaluatedKey);
        return  [
            'items' => $itemList,
            'count' => $itemCount,
        ];
    }   

    static public function scanAllItems($options) {
        $client = AWS::createDynamoDb();
        $itemList = [];
        $itemCount = 0;
        $lastEvaluatedKey = null; // Dynamo的分頁key
        do {
            if ($lastEvaluatedKey) {
                $options['ExclusiveStartKey'] = $lastEvaluatedKey;
            }
            $result = $client->scan($options);
            $itemList = array_merge($itemList,  AwsDynamo::unmarshalerList($result['Items']) ?? []);
            $itemCount +=  $result['Count'];
            $lastEvaluatedKey = $result['LastEvaluatedKey'] ?? null;
        } while ($lastEvaluatedKey);
        return  [
            'items' => $itemList,
            'count' => $itemCount,
        ];
    } 

    static public function BatchWriteItem($tableName, $items) {

        $client = AWS::createDynamoDb();
        $dynamoItem = self::marshalerPutItemList($items);

        $options = [
            'RequestItems' => [
                $tableName => []
            ],
        ];

        $MAX_WRITE_COUNT = 25;
        $itemCount = count($items);
        for ($i=0; $i<$itemCount; $i+=$MAX_WRITE_COUNT) {
            $cutArray = array_slice($dynamoItem, $i, $MAX_WRITE_COUNT);
            $options['RequestItems'][$tableName] = $cutArray;
            $result = $client->BatchWriteItem($options);
        };

        return $result;
    } 

    static public function commonQuery(string $version, string $tableName, 
        array $pKeyValue, 
        array $tKeyRangeValue = ['tk_datetimeIso' => ['0000', '9999']], 
        string $tKeyType='S')
    {
        # pk
        if (count($pKeyValue) !== 1 ) {
            throw new Exception('pKeyValue param length is must only 1');
        }
        $pkName = array_key_first($pKeyValue);
        $pkValue = $pKeyValue[$pkName];

        # tk
        if (count($tKeyRangeValue) !== 1 ) {
            throw new Exception('tKeyRangeValue param length is must only 1');
        }
        $tkName = array_key_first($tKeyRangeValue);
        if (count($tKeyRangeValue[$tkName]) !== 2 ) {
            throw new Exception('tKeyRangeValue param 只允許兩個值');
        }
        $tkStart = $tKeyRangeValue[$tkName][0];
        $tkEnd = $tKeyRangeValue[$tkName][1];

        # request 
        $client = AWS::createDynamoDb();
        $options = [
            'TableName' => AwsDynamo::getVersionTable($version, $tableName),
            'KeyConditionExpression' => 
                '#pk = :pk and (#tk BETWEEN :pkStart AND :pkEnd)',
            'ExpressionAttributeNames' => [
                '#pk' => $pkName,
                '#tk' => $tkName
            ],
            'ExpressionAttributeValues' => [
                ':pk'         => ['S' => $pkValue],
                ':pkStart'    => [$tKeyType => $tkStart],
                ':pkEnd'      => [$tKeyType => $tkEnd],
            ],
            'ConsistentRead' => false,
            'ReturnConsumedCapacity'    => 'TOTAL'
        ];
        $result = self::queryAllItems($options);

        return $result;
    }
}
