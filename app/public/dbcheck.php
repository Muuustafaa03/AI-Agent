<?php
require __DIR__ . '/../src/bootstrap.php';
header('Content-Type: text/plain');
echo "DB_HOST=".env('DB_HOST')."\n";
echo "DB_USER=".env('DB_USERNAME')."\n";
echo "DB_NAME=".env('DB_DATABASE')."\n";
$m = new mysqli(env('DB_HOST'), env('DB_USERNAME'), env('DB_PASSWORD'), env('DB_DATABASE'), (int)env('DB_PORT',3306));
echo $m->connect_error ? ("CONNECT_ERROR=".$m->connect_error) : "OK";
