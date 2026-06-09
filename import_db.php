<?php
$host = '127.0.0.1';
$port = 3306;
$db   = 'beekeeping_dashboard';
$user = 'beekeeping';
$pass = 'Beekeeping@2026';

$sqlFile = __DIR__ . '/beekeeping.sql';
if (!file_exists($sqlFile)) {
    die("SQL file not found.");
}

$command = "mysql -h {$host} -P {$port} -u {$user} -p'{$pass}' {$db} < {$sqlFile} 2>&1";
$output = shell_exec($command);

echo "Output:\n" . $output;
echo "\nDONE";
?>
