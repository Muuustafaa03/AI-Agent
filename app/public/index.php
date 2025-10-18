<?php
require_once __DIR__ . '/../src/bootstrap.php';
use App\Controllers\RunController;

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/' || $path === '/index.php') {
  include __DIR__ . '/landing.php';
  exit;
}

if ($path === '/runs' && $method === 'POST') {
  (new RunController())->create();
  exit;
}

if ($path === '/runs' && $method === 'GET') {
  (new RunController())->list();
  exit;
}

http_response_code(404);
echo "Not Found";