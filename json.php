<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$url = "https://www.sacnilk.com/snpage";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_ENCODING, '');
curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 🛠 disable SSL verification for testing

$html = curl_exec($ch);

if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch);
    exit;
}

curl_close($ch);

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($html);
libxml_clear_errors();

$xpath = new DOMXPath($dom);
$items = $xpath->query('//div[@id="recentquicknews"]/a');

$data = [];

foreach ($items as $aTag) {
    $href = $aTag->getAttribute('href');
    $bTag = $aTag->getElementsByTagName('b')->item(0);
    $name = $bTag ? trim($bTag->nodeValue) : '';

    if ($href && $name && stripos($name, "Box Office") !== false) {
        $data[] = [
            'name' => $name,
            'link' => 'https://www.sacnilk.com' . $href
        ];
    }
}

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
