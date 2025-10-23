<?php
require_once __DIR__ . '/../src/bootstrap.php';

use App\Controllers\RunController;
use App\Controllers\AnalyticsController;
use App\Controllers\ErrorsController;

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Landing
if ($path === '/' || $path === '/index.php') {
  include __DIR__ . '/landing.php';
  exit;
}

// Runs (list/show/create/save/delete)
if ($path === '/runs' && $method === 'GET') {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $c = new RunController();
  if ($id > 0) { $c->show($id); } else { $c->list(); }
  exit;
}
if ($path === '/runs' && $method === 'POST') {
  (new RunController())->create(); exit;
}
if ($path === '/runs/save' && $method === 'POST') {
  (new RunController())->save(); exit;
}
if ($path === '/runs/delete' && $method === 'GET') {
  (new RunController())->delete(); exit;
}

// Analytics
if ($path === '/analytics' && $method === 'GET') {
  (new AnalyticsController())->page(); exit;
}
if ($path === '/analytics/export' && $method === 'GET') {
  (new AnalyticsController())->export(); exit;
}

// Errors
if ($path === '/errors' && $method === 'GET') {
  (new ErrorsController())->page(); exit;
}

http_response_code(404);
echo "Not Found";
