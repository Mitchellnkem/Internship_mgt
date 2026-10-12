<?php
/** Same-origin, cached proxy for external opportunity listings. */
require_once dirname(__DIR__).'/core/init.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=60');

function opportunity_json_response($status, $payload) {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    opportunity_json_response(405, array('ok' => false, 'message' => 'Use GET to search opportunities.'));
}

$query = isset($_GET['q']) && is_string($_GET['q']) ? trim(strip_tags($_GET['q'])) : '';
$query = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $query);
$query = function_exists('mb_substr') ? mb_substr($query, 0, 100, 'UTF-8') : substr($query, 0, 100);
$scope = isset($_GET['scope']) && is_string($_GET['scope']) ? strtolower($_GET['scope']) : 'ghana';
$page = isset($_GET['page']) ? max(1, min(5, (int)$_GET['page'])) : 1;
$locations = array('ghana' => 'Ghana', 'africa' => 'Africa', 'international' => 'Worldwide');
if (!isset($locations[$scope])) {
    opportunity_json_response(400, array('ok' => false, 'message' => 'Choose Ghana, Africa, or International.'));
}
if ($query === '') {
    $query = 'internship';
} elseif (stripos($query, 'intern') === false && stripos($query, 'placement') === false) {
    $query .= ' internship';
}

if (!JOOBLE_API_KEY) {
    opportunity_json_response(200, array(
        'ok' => true,
        'configured' => false,
        'provider' => 'Jooble',
        'scope' => $scope,
        'items' => array(),
        'message' => 'Live search is not connected yet. Add a Jooble API key to enable external opportunities.'
    ));
}
if (!function_exists('curl_init')) {
    error_log('BIT-InternshipHub live search unavailable: PHP cURL extension is disabled.');
    opportunity_json_response(503, array('ok' => false, 'message' => 'Live search is temporarily unavailable.'));
}

$cacheInput = strtolower($query).'|'.$scope.'|'.$page;
$cacheKey = hash('sha256', $cacheInput);
$cache = $db->prepare('SELECT response_json FROM external_opportunity_search_cache WHERE cache_key = ? AND expires_at > NOW() LIMIT 1');
if ($cache) {
    $cache->bind_param('s', $cacheKey);
    $cache->execute();
    $cachedRow = $cache->get_result()->fetch_assoc();
    $cache->close();
    if ($cachedRow) {
        header('X-Opportunity-Cache: HIT');
        echo $cachedRow['response_json'];
        exit;
    }
}

// Throttle cache misses per client so a public endpoint cannot burn the
// provider quota with a stream of unique searches. Repeated cached searches
// do not consume this allowance.
$clientIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
$clientHash = hash_hmac('sha256', $clientIp, JOOBLE_API_KEY);
$rate = $db->prepare('INSERT INTO external_search_api_rate_limits (client_hash, window_started_at, request_count) VALUES (?, NOW(), 1) ON DUPLICATE KEY UPDATE request_count = IF(window_started_at < DATE_SUB(NOW(), INTERVAL 1 HOUR), 1, LEAST(request_count + 1, 11)), window_started_at = IF(window_started_at < DATE_SUB(NOW(), INTERVAL 1 HOUR), NOW(), window_started_at)');
if (!$rate) {
    error_log('BIT-InternshipHub could not prepare external search rate limit.');
    opportunity_json_response(503, array('ok' => false, 'message' => 'Live search is temporarily unavailable.'));
}
$rate->bind_param('s', $clientHash);
$rate->execute();
$rate->close();
$rateCheck = $db->prepare('SELECT request_count FROM external_search_api_rate_limits WHERE client_hash = ? LIMIT 1');
$rateCheck->bind_param('s', $clientHash);
$rateCheck->execute();
$rateRow = $rateCheck->get_result()->fetch_assoc();
$rateCheck->close();
if (!$rateRow || (int)$rateRow['request_count'] > 10) {
    header('Retry-After: 3600');
    opportunity_json_response(429, array('ok' => false, 'message' => 'You have reached the live search limit. Please try again later.'));
}

$payload = json_encode(array(
    'keywords' => $query,
    'location' => $locations[$scope],
    'page' => (string)$page,
    'ResultOnPage' => '12',
    'companysearch' => 'false'
));
$curl = curl_init('https://jooble.org/api/'.rawurlencode(JOOBLE_API_KEY));
curl_setopt_array($curl, array(
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Accept: application/json'),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_TIMEOUT => 8,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_USERAGENT => 'BIT-InternshipHub opportunity search'
));
$rawResponse = curl_exec($curl);
$httpStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

if ($rawResponse === false || $httpStatus < 200 || $httpStatus >= 300) {
    error_log('BIT-InternshipHub Jooble request failed: HTTP '.$httpStatus.' '.$curlError);
    opportunity_json_response(503, array('ok' => false, 'message' => 'Live opportunities could not be loaded right now. Try again shortly.'));
}
$remote = json_decode($rawResponse, true);
if (!is_array($remote) || !isset($remote['jobs']) || !is_array($remote['jobs'])) {
    error_log('BIT-InternshipHub Jooble returned an invalid response.');
    opportunity_json_response(502, array('ok' => false, 'message' => 'The opportunity provider returned an invalid response.'));
}

$items = array();
foreach (array_slice($remote['jobs'], 0, 12) as $job) {
    if (!is_array($job)) continue;
    $url = isset($job['link']) && is_string($job['link']) ? $job['link'] : '';
    if (filter_var($url, FILTER_VALIDATE_URL) === false || strtolower(parse_url($url, PHP_URL_SCHEME)) !== 'https') continue;
    $items[] = array(
        'title' => isset($job['title']) ? (string)$job['title'] : 'Opportunity',
        'company' => isset($job['company']) ? (string)$job['company'] : (isset($job['source']) ? (string)$job['source'] : 'External employer'),
        'location' => isset($job['location']) ? (string)$job['location'] : $locations[$scope],
        'description' => isset($job['snippet']) ? (string)$job['snippet'] : '',
        'type' => isset($job['type']) ? (string)$job['type'] : 'Opportunity',
        'updated' => isset($job['updated']) ? (string)$job['updated'] : '',
        'url' => $url,
        'source' => 'Jooble'
    );
}
$response = json_encode(array(
    'ok' => true,
    'configured' => true,
    'provider' => 'Jooble',
    'scope' => $scope,
    'total' => isset($remote['totalCount']) ? (int)$remote['totalCount'] : count($items),
    'items' => $items,
    'message' => empty($items) ? 'No external results found. Try different keywords or another region.' : ''
));

// Cache for two hours to reduce external API traffic and protect the provider quota.
$save = $db->prepare('INSERT INTO external_opportunity_search_cache (cache_key, response_json, fetched_at, expires_at) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 2 HOUR)) ON DUPLICATE KEY UPDATE response_json = VALUES(response_json), fetched_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL 2 HOUR)');
if ($save) {
    $save->bind_param('ss', $cacheKey, $response);
    $save->execute();
    $save->close();
}
header('X-Opportunity-Cache: MISS');
echo $response;
