<?php
/**
 * Laya AI — Production PHP Reverse Proxy for Hostinger hPanel & cPanel
 * 
 * Forwards frontend requests from Hostinger/cPanel to VPS API at http://187.52.117.2:8000
 * Handles:
 * - Mixed Content (HTTPS on Hostinger -> cURL to HTTP VPS)
 * - CORS (Cross-Origin Resource Sharing)
 * - Content-Type preservation for JSON and form payloads
 * - Complete Token Forwarding (X-Admin-Token, X-User-Token, X-API-Key, Authorization)
 * - Query strings and parameter preservation
 */

// Handle CORS
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header("Access-Control-Allow-Origin: " . ($origin !== '*' ? $origin : '*'));
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-User-Token, X-Admin-Token, x-api-key, x-user-token, x-admin-token, Cookie");

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$backend_base = "http://187.52.117.2:8000";

// Extract target endpoint
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
if (empty($endpoint) && isset($_SERVER['PATH_INFO'])) {
    $endpoint = $_SERVER['PATH_INFO'];
}

// Clean and unwrap endpoint recursively if double-encoded or prefixed with api_proxy.php
while (strpos($endpoint, 'api_proxy.php') !== false) {
    if (preg_match('/endpoint=([^&]+)/', $endpoint, $matches)) {
        $endpoint = urldecode($matches[1]);
    } else {
        $endpoint = str_replace('api_proxy.php', '', $endpoint);
    }
}
$endpoint = urldecode($endpoint);
$endpoint = trim($endpoint);
$endpoint = ltrim($endpoint, '/');

// Strip /laya/ or /jev/ prefix if present so all routes are clean
if (strpos($endpoint, 'laya/') === 0) {
    $endpoint = substr($endpoint, 5);
} elseif (strpos($endpoint, 'jev/') === 0) {
    $endpoint = substr($endpoint, 4);
}
$endpoint = ltrim($endpoint, '/');

if (empty($endpoint)) {
    $endpoint = 'health';
}

// Ensure single leading slash
$endpoint = '/' . $endpoint;

// Preserve other query parameters
$queryParams = $_GET;
unset($queryParams['endpoint']);
$queryString = http_build_query($queryParams);
$targetUrl = rtrim($backend_base, '/') . $endpoint;
if (!empty($queryString)) {
    $targetUrl .= (strpos($targetUrl, '?') !== false ? '&' : '?') . $queryString;
}

// Initialize cURL
$ch = curl_init($targetUrl);
$method = $_SERVER['REQUEST_METHOD'];
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 45);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

// Read request body for POST/PUT/PATCH/DELETE
$body = '';
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    $body = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
}

// Gather all headers safely
$headers = [];
if (function_exists('getallheaders')) {
    $rawHeaders = getallheaders();
    if (is_array($rawHeaders)) {
        $headers = $rawHeaders;
    }
}

// Also scan $_SERVER for HTTP_ headers (LiteSpeed & CGI compatibility)
foreach ($_SERVER as $key => $value) {
    if (substr($key, 0, 5) === 'HTTP_') {
        $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
        if (!isset($headers[$header])) {
            $headers[$header] = $value;
        }
    }
}

// Ensure Content-Type is captured from $_SERVER
if (empty($headers['Content-Type']) && !empty($_SERVER['CONTENT_TYPE'])) {
    $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
}
// Default to application/json if body looks like JSON
if (empty($headers['Content-Type']) && !empty($body)) {
    $trimmed = trim($body);
    if ((substr($trimmed, 0, 1) === '{' && substr($trimmed, -1) === '}') ||
        (substr($trimmed, 0, 1) === '[' && substr($trimmed, -1) === ']')) {
        $headers['Content-Type'] = 'application/json';
    }
}

// Explicit Admin Token forwarding (captures LiteSpeed stripping & query parameter fallback)
$admin_token = '';
if (!empty($_SERVER['HTTP_X_ADMIN_TOKEN'])) $admin_token = $_SERVER['HTTP_X_ADMIN_TOKEN'];
else if (!empty($_SERVER['REDIRECT_HTTP_X_ADMIN_TOKEN'])) $admin_token = $_SERVER['REDIRECT_HTTP_X_ADMIN_TOKEN'];
else if (!empty($_GET['admin_token'])) $admin_token = $_GET['admin_token'];
else if (!empty($headers['X-Admin-Token'])) $admin_token = $headers['X-Admin-Token'];
else if (!empty($headers['x-admin-token'])) $admin_token = $headers['x-admin-token'];

if (!empty($admin_token)) {
    $headers['X-Admin-Token'] = $admin_token;
    if (empty($headers['Authorization'])) {
        $headers['Authorization'] = 'Bearer ' . $admin_token;
    }
}

// Explicit User Token forwarding
$user_token = '';
if (!empty($_SERVER['HTTP_X_USER_TOKEN'])) $user_token = $_SERVER['HTTP_X_USER_TOKEN'];
else if (!empty($_SERVER['REDIRECT_HTTP_X_USER_TOKEN'])) $user_token = $_SERVER['REDIRECT_HTTP_X_USER_TOKEN'];
else if (!empty($_GET['user_token'])) $user_token = $_GET['user_token'];
else if (!empty($headers['X-User-Token'])) $user_token = $headers['X-User-Token'];
else if (!empty($headers['x-user-token'])) $user_token = $headers['x-user-token'];

if (!empty($user_token)) {
    $headers['X-User-Token'] = $user_token;
}

// Explicit API Key forwarding
$api_key = '';
if (!empty($_SERVER['HTTP_X_API_KEY'])) $api_key = $_SERVER['HTTP_X_API_KEY'];
else if (!empty($_SERVER['REDIRECT_HTTP_X_API_KEY'])) $api_key = $_SERVER['REDIRECT_HTTP_X_API_KEY'];
else if (!empty($_GET['api_key'])) $api_key = $_GET['api_key'];
else if (!empty($headers['X-API-Key'])) $api_key = $headers['X-API-Key'];
else if (!empty($headers['x-api-key'])) $api_key = $headers['x-api-key'];

if (!empty($api_key)) {
    $headers['X-API-Key'] = $api_key;
}

// Ensure Authorization header is preserved from all Apache/LiteSpeed variants
if (empty($headers['Authorization'])) {
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } else if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
    }
}

// Build final cURL header list
$forwardHeaders = [];
foreach ($headers as $name => $value) {
    $lower = strtolower($name);
    if ($lower === 'host' || $lower === 'content-length') {
        continue;
    }
    $forwardHeaders[] = "$name: $value";
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);

// Execute request
$response = curl_exec($ch);
if (curl_errno($ch)) {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'detail' => 'Laya Hostinger Proxy Connection Error: ' . curl_error($ch),
        'target' => $targetUrl
    ]);
    curl_close($ch);
    exit();
}

$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$raw_headers = substr($response, 0, $header_size);
$response_body = substr($response, $header_size);
curl_close($ch);

http_response_code($http_code);

// Forward response headers (strip hop-by-hop and length/encoding headers so LiteSpeed/Apache doesn't mismatch or hang)
$header_lines = explode("\r\n", $raw_headers);
$skip_headers = ['http/', 'transfer-encoding:', 'content-length:', 'connection:', 'content-encoding:', 'keep-alive:'];
foreach ($header_lines as $h) {
    if (empty($h)) continue;
    $lower = strtolower($h);
    $should_skip = false;
    foreach ($skip_headers as $skip) {
        if (strpos($lower, $skip) === 0) {
            $should_skip = true;
            break;
        }
    }
    if ($should_skip) continue;
    header($h, false);
}

echo $response_body;
?>
