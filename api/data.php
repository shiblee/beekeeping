<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Later: replace with real DB queries
$data = [
    'total_beekeepers'   => 10482,
    'total_honey_tonnes' => 48300,
    'avg_income_inr'     => 72600,
    'active_colonies'    => 2400000,
    'districts_covered'  => 75,
    'avg_loss_rate_pct'  => 14.2,
    'avg_honey_price_2023' => 212,
    'profit_margin_pct'  => 57,
];

echo json_encode($data, JSON_PRETTY_PRINT);
