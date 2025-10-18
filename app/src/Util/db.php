<?php
function db() {
  static $conn;
  if ($conn) return $conn;
  $host = env('DB_HOST','mysql');
  $port = env('DB_PORT','3306');
  $db   = env('DB_DATABASE','agentdb');
  $user = env('DB_USERNAME','agentuser');
  $pass = env('DB_PASSWORD','agentpass');
  $conn = new mysqli($host, $user, $pass, $db, (int)$port);
  if ($conn->connect_error) {
    die('DB connect error: ' . $conn->connect_error);
  }
  return $conn;
}