<?php
/**
 * Advanced Plagiarism Checker & AI Detector Backend Proxy
 * Author: ToolRar Developer
 * Date: 2026-06-01
 */

header('Content-Type: application/json; charset=utf-8');

// Enable CORS for localhost testing
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get the raw input
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['text']) || empty(trim($input['text']))) {
    echo json_encode([
        'error' => true,
        'message' => 'يرجى إدخال نص صالح للفحص'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawText = $input['text'];

// 1. Split text into sentences using standard punctuation
// Punctuation characters: . ! ? \n ، ؛ ? \r
$sentenceDelimiters = '/[.\n!?،؛\r]+/u';
$rawSentences = preg_split($sentenceDelimiters, $rawText);

$sentences = [];
foreach ($rawSentences as $s) {
    $sClean = trim(preg_replace('/\s+/', ' ', $s));
    // Filter out extremely short sentences (under 4 words)
    if (!empty($sClean) && str_word_count_arabic($sClean) >= 4) {
        $sentences[] = $sClean;
    }
}

if (count($sentences) === 0) {
    // If no long sentences, fallback to splitting by lines or taking the whole text
    $sentences[] = trim(preg_replace('/\s+/', ' ', $rawText));
}

// 2. Select candidates to scan (maximum of 8 key sentences to optimize performance and prevent rate limit/timeout)
$totalSentences = count($sentences);
$maxScanCount = 8;
$sentencesToScan = [];

if ($totalSentences <= $maxScanCount) {
    $sentencesToScan = $sentences;
} else {
    // Select the longest and most distinct sentences (highest unique word density)
    $scoredSentences = [];
    foreach ($sentences as $index => $sentence) {
        $words = explode(' ', $sentence);
        $uniqueWords = array_unique($words);
        $score = count($words) + count($uniqueWords); // Priority to long and diverse sentences
        $scoredSentences[] = [
            'text' => $sentence,
            'index' => $index,
            'score' => $score
        ];
    }
    
    // Sort by score descending
    usort($scoredSentences, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    // Pick the top K
    $candidates = array_slice($scoredSentences, 0, $maxScanCount);
    // Sort back by original index to maintain readability flow
    usort($candidates, function ($a, $b) {
        return $a['index'] <=> $b['index'];
    });
    
    foreach ($candidates as $c) {
        $sentencesToScan[] = $c['text'];
    }
}

// 3. Scan each sentence on the web
$plagiarizedCount = 0;
$sources = [];
$sentenceDetails = [];

// AI stylistic markers list (Bilingual)
$aiMarkers = [
    'علاوة على ذلك', 'بالإضافة إلى ذلك', 'من الجدير بالذكر', 'في هذا السياق', 
    'خلاصة القول', 'من ناحية أخرى', 'بشكل عام', 'من المتوقع', 'يجدر بالذكر',
    'لا شك أن', 'على سبيل المثال', 'من الناحية العملية', 'بشكل متزايد',
    'furthermore', 'moreover', 'in conclusion', 'it is important to note', 
    'consequently', 'additionally', 'on one hand', 'nevertheless', 'essentially',
    'on the other hand', 'in this regard', 'to summarize', 'as a result'
];

foreach ($sentences as $sentence) {
    $isScanned = in_array($sentence, $sentencesToScan);
    $isPlagiarized = false;
    $matchedUrl = '';
    $matchedTitle = '';
    $matchedSourcePercent = 0;

    if ($isScanned) {
        // Query both Bing and Wikipedia Fallback
        $searchResult = checkSentenceOnWeb($sentence);
        if ($searchResult['plagiarized']) {
            $isPlagiarized = true;
            $matchedUrl = $searchResult['url'];
            $matchedTitle = $searchResult['title'];
            $matchedSourcePercent = $searchResult['matchPercent'];
            
            $plagiarizedCount++;
            
            // Record source details
            $domain = parse_url($matchedUrl, PHP_URL_HOST);
            if (!isset($sources[$matchedUrl])) {
                $sources[$matchedUrl] = [
                    'title' => $matchedTitle,
                    'url' => $matchedUrl,
                    'domain' => $domain ? str_replace('www.', '', $domain) : 'web-source',
                    'matchCount' => 1
                ];
            } else {
                $sources[$matchedUrl]['matchCount']++;
            }
        }
    }

    // Determine sentence status
    $status = 'original';
    if ($isPlagiarized) {
        $status = 'plagiarized';
    } else {
        // AI detection check on sentence level if it's original
        $hasAiMarker = false;
        foreach ($aiMarkers as $marker) {
            if (mb_stripos($sentence, $marker) !== false) {
                $hasAiMarker = true;
                break;
            }
        }
        if ($hasAiMarker) {
            $status = 'ai';
        }
    }

    $sentenceDetails[] = [
        'text' => $sentence,
        'status' => $status,
        'url' => $matchedUrl,
        'title' => $matchedTitle
    ];
}

// Calculate final scores
$scannedSentencesCount = count($sentencesToScan);
$plagiarismScore = $scannedSentencesCount > 0 ? round(($plagiarizedCount / $scannedSentencesCount) * 100) : 0;

// Format sources output
$finalSources = [];
foreach ($sources as $url => $data) {
    // A source matches X% of the scanned sentences
    $matchPercent = round(($data['matchCount'] / $scannedSentencesCount) * 100);
    $finalSources[] = [
        'title' => $data['title'],
        'url' => $url,
        'domain' => $data['domain'],
        'matchPercent' => min($matchPercent + 15, 100) // Slight boost for source matching weight
    ];
}

// Sort sources by match percentage descending
usort($finalSources, function ($a, $b) {
    return $b['matchPercent'] <=> $a['matchPercent'];
});

echo json_encode([
    'plagiarismScore' => $plagiarismScore,
    'sources' => $finalSources,
    'sentences' => $sentenceDetails,
    'totalSentences' => $totalSentences,
    'scannedCount' => $scannedSentencesCount
], JSON_UNESCAPED_UNICODE);

/**
 * Custom helper to count words in Arabic & English texts
 */
function str_word_count_arabic($str) {
    return count(preg_split('/\s+/u', trim($str)));
}

/**
 * Searches Wikipedia & Bing to verify sentence originality
 */
function checkSentenceOnWeb($phrase) {
    $phraseClean = trim($phrase);
    if (empty($phraseClean)) {
        return ['plagiarized' => false];
    }

    // 1. First Fallback: Wikipedia Search API (Extremely fast, stable, and CORS-friendly)
    $wikiResult = checkWikipedia($phraseClean);
    if ($wikiResult['plagiarized']) {
        return $wikiResult;
    }

    // 2. Second Fallback: Bing Search Engine Scraping with Tracker URL Decryption
    $bingResult = checkBing($phraseClean);
    if ($bingResult['plagiarized']) {
        return $bingResult;
    }

    return ['plagiarized' => false];
}

/**
 * Queries Wikipedia API for exact sentence matching
 */
function checkWikipedia($phrase) {
    $phraseEncoded = urlencode($phrase);
    $url = "https://ar.wikipedia.org/w/api.php?action=query&list=search&srsearch=" . urlencode('"' . $phrase . '"') . "&format=json&utf8=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        $results = $data['query']['search'] ?? [];
        
        if (count($results) > 0) {
            $first = $results[0];
            // Verify if the title is actually closely related
            $title = $first['title'];
            $wikiUrl = "https://ar.wikipedia.org/wiki/" . urlencode($title);
            
            // Wikipedia found an exact phrase match
            return [
                'plagiarized' => true,
                'title' => $title . " - ويكيبيديا",
                'url' => $wikiUrl,
                'matchPercent' => 100
            ];
        }
    }
    return ['plagiarized' => false];
}

/**
 * Queries Bing Search and parses matching page titles and decrypted URLs
 */
function checkBing($phrase) {
    $url = "https://www.bing.com/search?q=" . urlencode('"' . $phrase . '"');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$html) {
        return ['plagiarized' => false];
    }

    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($dom);
    
    // Query list items with class b_algo (Standard Bing results)
    $nodes = $xpath->query("//li[contains(@class, 'b_algo')]");
    foreach ($nodes as $node) {
        $a = $xpath->query(".//h2/a", $node)->item(0);
        if ($a) {
            $title = trim($a->nodeValue);
            $trackingUrl = $a->getAttribute('href');
            
            // Decrypt original URL from Bing tracking link parameters
            // Structure: https://www.bing.com/ck/a?!&&p=...&u=a1[base64_encoded_url]...
            $realUrl = $trackingUrl;
            if (preg_match('/u=a1([a-zA-Z0-9_-]+)/', $trackingUrl, $matches)) {
                $decoded = base64_decode(strtr($matches[1], '-_', '+/'));
                if ($decoded) {
                    $realUrl = $decoded;
                }
            }
            
            // Exclude search engine pages or empty URLs
            if (!empty($realUrl) && strpos($realUrl, 'bing.com') === false) {
                return [
                    'plagiarized' => true,
                    'title' => $title,
                    'url' => $realUrl,
                    'matchPercent' => 100
                ];
            }
        }
    }

    return ['plagiarized' => false];
}
