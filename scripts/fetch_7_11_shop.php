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
        return '"' . trim($value) . '"';
    }
}

$ch = curl_init('http://emap.pcsc.com.tw/EMapSDK.aspx');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

foreach ($cityids as $cityid => $cityname) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(GetTownParams($cityid)));
    $ret = curl_exec($ch);
    //file_put_contents($cityid . '.xml', $ret);

    $doc = DOMDocument::loadXML($ret);
    $ele_towns = $doc->getElementsByTagName('GeoPosition');
    foreach ($ele_towns as $ele_town) {
        $townname = $ele_town->getElementsByTagName('TownName')->item(0)->nodeValue;
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(SearchStoreParams($cityname, $townname)));
        $geo_ret = curl_exec($ch);
        $geo_doc = DOMDocument::loadXML($geo_ret);
        $ele_positions = $geo_doc->getElementsByTagName('GeoPosition');
        foreach ($ele_positions as $ele_position) {
            echo '  {' . "\n";
            foreach ($ele_position->childNodes as $node) {
                // 最後一行 Store_URL 會多出 ","
                echo '    "' . $node->nodeName . '": ' . convertValue($node->nodeName, $node->nodeValue) . ',' . "\n";
            }
            echo '  },' . "\n";
        }
    }
}

// 輸出後再手動修正多出來的逗號 XD
curl_close($ch);
