<?php
$host = '127.0.0.1';
$port = 3306;
$db   = 'beekeeping_dashboard';
$user = 'beekeeping';
$pass = 'Beekeeping@2026';

$pdo = new PDO(
    "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
