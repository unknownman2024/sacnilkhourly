<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$url = "https://www.sacnilk.com/metasection/box_office";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_ENCODING, '');
curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$html = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["error" => curl_error($ch)]);
    exit;
}
curl_close($ch);

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($html);
libxml_clear_errors();

$xpath = new DOMXPath($dom);
$newsDivs = $xpath->query('//div[contains(@class, "relatednewssidemainshort")]/a');

$allMovies = [];

foreach ($newsDivs as $linkNode) {
    $href = $linkNode->getAttribute('href');
    $titleNode = $xpath->query('.//b', $linkNode)->item(0);
    $fullTitle = $titleNode ? trim($titleNode->textContent) : '';

    if ($href && $fullTitle && stripos($fullTitle, 'Box Office') !== false) {
        if (preg_match('/^(.*?) Box Office Collection Day (\d+)/i', $fullTitle, $matches)) {
            $rawTitle = trim($matches[1]);
            $day = (int)$matches[2];

            // Normalize title
            $normalized = preg_replace('/\b(19|20)\d{2}\b/', '', $rawTitle);
            $normalized = strtolower(trim(preg_replace('/[^a-z0-9]+/i', ' ', $normalized)));

            // Store all day entries per movie
            if (!isset($allMovies[$normalized])) {
                $allMovies[$normalized] = [];
            }

            $allMovies[$normalized][] = [
                'name' => $fullTitle,
                'link' => 'https://www.sacnilk.com' . $href,
                'day' => $day
            ];
        }
    }
}

$finalOutput = [];

// Now process and pick highest-day entry per movie, if <= 10
foreach ($allMovies as $entries) {
    // Sort entries by day DESC
    usort($entries, function ($a, $b) {
        return $b['day'] - $a['day'];
    });

    $top = $entries[0]; // Highest day

    if ($top['day'] <= 10) {
        $finalOutput[] = [
            'name' => $top['name'],
            'link' => $top['link']
        ];
    }
}

echo json_encode($finalOutput, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
