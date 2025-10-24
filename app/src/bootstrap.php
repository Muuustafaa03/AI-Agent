<?php
// Simple env loader
function env(string $key, $default = null) {
  static $loaded = false, $env = [];
  if (!$loaded) {
    $file = __DIR__ . '/../.env';
    if (is_file($file)) {
      foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(ltrim($line), '#') === 0) continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim(trim($v), "\"'");
      }
    }
    $loaded = true;
  }
  return $env[$key] ?? $default;
}

<?php
// src/bootstrap.php
declare(strict_types=1);

require __DIR__.'/Util/env.php';
require __DIR__.'/Util/db.php';

// AUTO-MIGRATE (creates tables if missing)
require __DIR__.'/Util/migrate.php';
migrate_once();

// …the rest of your bootstrap (autoloaders, etc.)


// Minimal PSR-4 autoloader for App\*
spl_autoload_register(function ($class) {
  $prefix = 'App\\';
  if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
  $rel = substr($class, strlen($prefix));            // e.g. 'Controllers\\RunController'
  $relPath = str_replace('\\', DIRECTORY_SEPARATOR, $rel) . '.php';
  $file = __DIR__ . '/' . $relPath;                  // maps to app/src/<...>.php
  if (is_file($file)) require $file;
});


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
