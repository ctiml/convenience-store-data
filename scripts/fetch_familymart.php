<?php

$v_top = 26.931603;
$v_left = 117.770208;
$v_bottom = 20.444745;
$v_right = 125.026337;

$url = 'http://api.map.com.tw/net/familyShop.aspx?l=10&searchType=ShowStore&type=&vLeft=' . $v_left . '&vRight=' . $v_right . '&vTop=' . $v_top . '&vBottom=' . $v_bottom . '&fun=addSmallShop';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$ret = curl_exec($ch);

$cities = array();

if (preg_match('/addSmallShop\((.*)\)/s', $ret, $m)) {
    $json_array = json_decode($m[1]);
    foreach ($json_array as $json) {
        // 修正異體字
        $json->addr = str_replace('巿', '市', $json->addr);
        $city = mb_substr($json->addr, 0, 3, "UTF-8");
        if (!array_key_exists($city, $cities)) {
            $cities[$city] = array(
                'city_name' => $city,
                'stores' => array(),
            );
        }
        $cities[$city]['stores'][] = $json;
    }
}

foreach ($cities as $city => $json) {
    file_put_contents($city . '.json', json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}
