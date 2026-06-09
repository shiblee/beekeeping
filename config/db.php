<?php
$host = '127.0.0.1';
$port = 8889;
$db   = 'beekeeping';
$user = 'root';
$pass = 'root';

$pdo = new PDO(
    "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
