<?php

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://www.hilife.com.tw/storeInquiry_street.aspx');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, 'http://www.hilife.com.tw/getGoogleSpot.ashx');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);

$ret = curl_exec($ch);
$doc = @DOMDocument::loadHTML($ret);
$ele_city = $doc->getElementById('CITY');

curl_setopt($ch, CURLOPT_POST, true);
foreach ($ele_city->childNodes as $node) {
    $city_name = $node->nodeValue;
    //$city_name = '台北市';
    $json = array(
        'city_name' => $city_name,
        'stores' => array(),
    );
    echo $city_name . "\n";

    $params = array(
        '__EVENTTARGET' => 'CITY',
        '__VIEWSTATE' => $doc->getElementById('__VIEWSTATE')->getAttribute('value'),
        '__VIEWSTATEGENERATOR' => $doc->getElementById('__VIEWSTATEGENERATOR')->getAttribute('value'),
        '__EVENTVALIDATION' => $doc->getElementById('__EVENTVALIDATION')->getAttribute('value'),
        'CITY' => $city_name,
    );
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    $ret = curl_exec($ch);
    $city_doc = @DOMDocument::loadHTML($ret);
    $ele_area = $city_doc->getElementById('AREA');
    foreach ($ele_area->childNodes as $node) {
        // 先抓經緯度資料
        curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query(array(
            'city_name' => $city_name,
            'town_name' => $node->nodeValue,
            'shop_id' => '',
        )));

        $spots = array();
        $ret = curl_exec($ch2);
        foreach (json_decode($ret) as $s) {
            $key = str_replace('萊爾富 ', '', $s->名稱);
            $service = '';
            if (preg_match_all("/title='([^']+)'/", $s->說明, $m)) {
                $service = implode(',', $m[1]);   
            }
            $spots[$key] = array(
                'lat' => $s->緯度,
                'lng' => $s->經度,
                'service' => $service,
            );
        }

        $params = array(
            '__EVENTTARGET' => 'AREA',
            '__VIEWSTATE' => $city_doc->getElementById('__VIEWSTATE')->getAttribute('value'),
            '__VIEWSTATEGENERATOR' => $city_doc->getElementById('__VIEWSTATEGENERATOR')->getAttribute('value'),
            '__EVENTVALIDATION' => $city_doc->getElementById('__EVENTVALIDATION')->getAttribute('value'),
            'CITY' => $city_name,
            'AREA' => $node->nodeValue,
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $ret = curl_exec($ch);
        $area_doc = @DOMDocument::loadHTML($ret);
        $xpath = new DOMXPath($area_doc);
        $results = $xpath->query("//*[@class='searchResults']/table/tr");
        foreach ($results as $r) {
            $store_name = $r->childNodes->item(0)->nodeValue;
            echo $store_name . "\n";
            $store = array(
                'name' => $store_name,
                'address' => $r->childNodes->item(2)->nodeValue,
                'phone' => $r->childNodes->item(4)->nodeValue,
                'lat' => $spots[$store_name]['lat'],
                'lng' => $spots[$store_name]['lng'],
                'service' => $spots[$store_name]['service'],
            );
            if (preg_match('#shop_id=(\d+)#', $r->childNodes->item(6)->firstChild->getAttribute('onclick'), $matches)) {
                $store['_id'] = $matches[1];
            }
            $json['stores'][] = $store;
        }
    }

    file_put_contents($city_name . ".json", json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}
