<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set("Asia/Kolkata");

function fetchJsonFromUrl($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // disable SSL checks
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        die("cURL Error: $error");
    }

    $json = json_decode($response, true);
    if (!$json) {
        die("Failed to decode JSON from $url");
    }

    return $json;
}

function fetchAmountFromPage($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // disable SSL checks
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return null;

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $hrStartNode = $xpath->query('//hr[@id="hrstart"]');

    if ($hrStartNode->length > 0) {
        $targetNode = $hrStartNode->item(0)->nextSibling;

        while ($targetNode && $targetNode->nodeType != XML_TEXT_NODE) {
            $targetNode = $targetNode->nextSibling;
        }

        if ($targetNode) {
            $text = trim($targetNode->nodeValue);
            preg_match('/around ([0-9.]+) Cr/i', $text, $matches);
            return $matches[1] ?? null;
        }
    }

    return null;
}

function getDayFromName($title) {
    preg_match('/Day\s+(\d+)/i', $title, $match);
    return $match[0] ?? 'Unknown Day';
}

function cleanMovieTitle($title) {
    return preg_replace('/\s+Box Office.*$/i', '', $title);
}

$jsonUrl = 'https://bo24.rf.gd/S/json.php';
$movieLinks = fetchJsonFromUrl($jsonUrl);

$outputFile = 'data.json';
$existing = file_exists($outputFile) ? json_decode(file_get_contents($outputFile), true) : [];

$existingMap = [];
foreach ($existing as $item) {
    $existingMap[$item['movie']] = $item['data'];
}

foreach ($movieLinks as $movie) {
    $nameRaw = $movie['name'];
    $link = $movie['link'];
    $amount = fetchAmountFromPage($link);

    if ($amount) {
        $movieName = cleanMovieTitle($nameRaw);
        $dataPoint = [
            "date" => date("Y-m-d"),
            "day" => getDayFromName($nameRaw),
            "time" => date("H:i:s"),
            "amount_cr" => $amount
        ];

        if (!isset($existingMap[$movieName])) {
            $existingMap[$movieName] = [];
        }

        // Avoid duplicates if same time already exists
        $alreadyExists = false;
        foreach ($existingMap[$movieName] as $entry) {
            if ($entry['time'] === $dataPoint['time'] && $entry['date'] === $dataPoint['date']) {
                $alreadyExists = true;
                break;
            }
        }

        if (!$alreadyExists) {
            $existingMap[$movieName][] = $dataPoint;
        }
    }
}

// Convert map back to array format
$final = [];
foreach ($existingMap as $movie => $dataEntries) {
    $final[] = [
        "movie" => $movie,
        "data" => $dataEntries
    ];
}

// Save to file (compact formatting)
file_put_contents($outputFile, json_encode($final, JSON_UNESCAPED_SLASHES));

echo "✅ Data fetched and saved at " . date("H:i:s");
?>
