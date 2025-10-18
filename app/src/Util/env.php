<?php
function env($key, $default=null) {
  static $loaded = false;
  static $map = [];
  if (!$loaded) {
    $envFile = __DIR__ . '/../../.env';
    if (file_exists($envFile)) {
      foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (!str_contains($line, '=')) continue;
        [$k,$v] = explode('=', $line, 2);
        $map[trim($k)] = trim($v);
      }
    }
    $loaded = true;
  }
  return $map[$key] ?? getenv($key) ?? $default;
}