<?php

$cityids = array(
    '01' => '台北市',
    '02' => '基隆市',
    '03' => '新北市',
    '04' => '桃園市',
    '05' => '新竹市',
    '06' => '新竹縣',
    '07' => '苗栗縣',
    '08' => '台中市',
    //'09' => '',
    '10' => '彰化縣',
    '11' => '南投縣',
    '12' => '雲林縣',
    '13' => '嘉義市',
    '14' => '嘉義縣',
    '15' => '台南市',
    //'16' => '',
    '17' => '高雄市',
    //'18' => '',
    '19' => '屏東縣',
    '20' => '宜蘭縣',
    '21' => '花蓮縣',
    '22' => '台東縣',
    '23' => '澎湖縣',
    '24' => '連江縣',
    '25' => '金門縣',
);

function GetTownParams($cityid) {
    return array(
        'commandid' => 'GetTown',
        'cityid' => $cityid,
        'isDining' => 'False',
        'isParking' => 'False',
        'isLavatory' => 'False',
        'isATM' => 'False',
        'is7WiFi' => 'False',
        'isIce' => 'False',
        'isHotDog' => 'False',
        'isHealthStations' => 'False',
        'isIceCream' => 'False',
        'isOpenStore' => 'False',
        'isFruit' => 'False',
        'isCityCafe' => 'False',
        'isUp' => 'False',
        'isOrganic' => 'False',
        'isCorn' => 'False',
        'isMakeUp' => 'False',
        'isMuji' => 'False',
        'isMD' => 'False',
    );
}

function SearchStoreParams($cityname, $townname) {
    return array(
        'commandid' => 'SearchStore',
        'city' => $cityname,
        'town' => $townname,
        'roadname' => '',
        'ID' => '',
        'StoreName' => '',
        'SpecialStore_Kind' => '',
        'isDining' => 'False',
        'isParking' => 'False',
        'isLavatory' => 'False',
        'isATM' => 'False',
        'is7WiFi' => 'False',
        'isIce' => 'False',
        'isHotDog' => 'False',
        'isHealthStations' => 'False',
        'isIceCream' => 'False',
        'isOpenStore' => 'False',
        'isFruit' => 'False',
        'isCityCafe' => 'False',
        'isUp' => 'False',
        'isOrganic' => 'False',
        'isCorn' => 'False',
        'isMakeUp' => 'False',
        'isMuji' => 'False',
        'isMD' => 'False',
        'address' => '',
    );
}

function convertValue($type, $value) {
    if ($type == 'POIID') {
        return intval(trim($value));
    } else if ($type == 'X') {
        return floatval(trim(substr_replace($value, '.', 3, 0)));
    } else if ($type == 'Y') {
        return floatval(trim(substr_replace($value, '.', 2, 0)));
    } else {
        return trim($value);
    }
}

$ch = curl_init('http://emap.pcsc.com.tw/EMapSDK.aspx');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$total_count = 0;

foreach ($cityids as $cityid => $cityname) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(GetTownParams($cityid)));
    $ret = curl_exec($ch);
    //file_put_contents($cityid . '.xml', $ret);

    $doc = DOMDocument::loadXML($ret);
    $ele_towns = $doc->getElementsByTagName('GeoPosition');

    $json = array(
        'city_id' => $cityid,
        'city_name' => $cityname,
        'stores' => array(),
    );

    $store_list = array();

    foreach ($ele_towns as $ele_town) {
        $townname = $ele_town->getElementsByTagName('TownName')->item(0)->nodeValue;
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(SearchStoreParams($cityname, $townname)));
        $geo_ret = curl_exec($ch);
        $geo_doc = DOMDocument::loadXML($geo_ret);
        $ele_positions = $geo_doc->getElementsByTagName('GeoPosition');
        foreach ($ele_positions as $ele_position) {
            $store = array();
            foreach ($ele_position->childNodes as $node) {
                $store[$node->nodeName] = convertValue($node->nodeName, $node->nodeValue);
            }
            // 檢查店號是否有重覆
            if (array_key_exists($store['POIID'], $store_list)) {
                $a = json_encode($store_list[$store['POIID']]);
                $b = json_encode($store);
                if ($a == $b) {
                    echo "Warning: " . $cityname . "發現重覆店號(" . $store['POIID'] . "," . $store['POIName'] . ")\n";
                    continue;
                }
            } else {
                $store_list[$store['POIID']] = $store;
            }
            $json['stores'][] = $store;
        }
    }

    echo $cityname . "店家數量：" . count($json['stores']) . "\n";
    $total_count += count($json['stores']);

    file_put_contents($cityid . '_' . $cityname . '.json', json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}

echo "店家數量合計：" . $total_count . "\n";
curl_close($ch);
