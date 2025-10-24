<?php
// src/bootstrap.php
declare(strict_types=1);

// Simple env loader - checks real ENV first, then .env file
function env(string $key, $default = null) {
  static $envFileLoaded = false, $envFromFile = [];
  
  // First, check if it exists as a real environment variable
  $value = getenv($key);
  if ($value !== false) {
    return $value;
  }
  
  // Fall back to .env file
  if (!$envFileLoaded) {
    $file = __DIR__ . '/../.env';
    if (is_file($file)) {
      foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(ltrim($line), '#') === 0) continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $envFromFile[trim($k)] = trim(trim($v), "\"'");
      }
    }
    $envFileLoaded = true;
  }
  
  return $envFromFile[$key] ?? $default;
}

// mysqli helper
function db(): mysqli {
  $host = env('DB_HOST', 'mysql');
  $port = (int)env('DB_PORT', 3306);
  $user = env('DB_USERNAME', 'root');
  $pass = env('DB_PASSWORD', '');
  $name = env('DB_DATABASE', 'agentdb');

  $m = new mysqli($host, $user, $pass, $name, $port);
  if ($m->connect_error) {
    throw new RuntimeException('DB connect error: ' . $m->connect_error);
  }
  $m->set_charset('utf8mb4');
  return $m;
}

// AUTO-MIGRATE (creates tables if missing)
require __DIR__.'/Util/migrate.php';
migrate_once();

// Minimal PSR-4 autoloader for App\*
spl_autoload_register(function ($class) {
  $prefix = 'App\\';
  if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
  $rel = substr($class, strlen($prefix));
  $relPath = str_replace('\\', DIRECTORY_SEPARATOR, $rel) . '.php';
  $file = __DIR__ . '/' . $relPath;
  if (is_file($file)) require $file;
});