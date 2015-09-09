<?php

function lookup($string){

    $string = str_replace (" ", "+", urlencode($string));
    $url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$string."&sensor=false";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);

    if ($response['status'] != 'OK') {
        return null;
    }

    $geometry = $response['results'][0]['geometry'];
    return array(
        'latitude' => $geometry['location']['lng'],
        'longitude' => $geometry['location']['lat'],
        'location_type' => $geometry['location_type'],
    );
}

$ch = curl_init('http://www.okmart.com.tw/convenient_shopSearch');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$ret = curl_exec($ch);

$doc = @DOMDocument::loadHTML($ret);
$ele_cities = $doc->getElementById('ctl00_ContentPlaceHolder1_ddlcity');
foreach ($ele_cities->childNodes as $ele) {
    $city = trim($ele->getAttribute('value'));
    echo $city . "\n";
    if (in_array($city, array('基隆市', '台北市', '新北市', '桃園市'))) {
        continue;
    }
    if (strlen($city) > 0) {
        $json = array(
            'city_name' => $city,
            'stores' => array(),
        );
        curl_setopt($ch, CURLOPT_URL, 'http://www.okmart.com.tw/convenient_shopSearch_Result.asp?city=' . urlencode($city));
        $ret = curl_exec($ch);
        if (preg_match_all('#<li><h2>([^<]+)</h2><span>([^<]+)</span>\s?<div><a href="javascript:showshop\(\'(\d+)\',\s?\'.*\'\);"\s?>[^<]+</a></div></li>#', $ret, $m)) {
            for ($i = 0; $i < count($m[1]); $i++) {
                $name = trim($m[1][$i]);
                $address = trim($m[2][$i]);
                echo $name . "\n";
                $latlng = lookup($address);
                $json['stores'][] = array(
                    'name' => $name,
                    'address' => $address,
                    'lat' => $latlng['latitude'],
                    'lng' => $latlng['longitude'], 
                    'id' => $m[3][$i],
                );
                sleep(1);
            }
        }
        file_put_contents($city . '.json', json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    }
}

