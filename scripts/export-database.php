<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$envPath = $root.DIRECTORY_SEPARATOR.'.env';
$exportPath = $root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'exports'.DIRECTORY_SEPARATOR.'erp_database.sql';

if (! file_exists($envPath)) {
    fwrite(STDERR, ".env file not found.\n");
    exit(1);
}

$env = [];

foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);

    if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
        continue;
    }

    [$key, $value] = explode('=', $line, 2);
    $env[trim($key)] = trim(trim($value), '"');
}

$database = $env['DB_DATABASE'] ?? null;
$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$username = $env['DB_USERNAME'] ?? 'root';
$password = $env['DB_PASSWORD'] ?? '';

if (! $database) {
    fwrite(STDERR, "DB_DATABASE is missing in .env.\n");
    exit(1);
}

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database),
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
);

$quoteIdentifier = static fn (string $value): string => '`'.str_replace('`', '``', $value).'`';
$quoteValue = static function (mixed $value) use ($pdo): string {
    if ($value === null) {
        return 'NULL';
    }

    return $pdo->quote((string) $value);
};

$lines = [
    '-- EXAD ERP database export',
    '-- Generated: '.date('Y-m-d H:i:s P'),
    '-- Database: '.$database,
    'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";',
    'SET time_zone = "+00:00";',
    'SET NAMES utf8mb4;',
    'SET FOREIGN_KEY_CHECKS = 0;',
    '',
];

$tables = $pdo
    ->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')
    ->fetchAll(PDO::FETCH_NUM);

foreach ($tables as $tableRow) {
    $table = $tableRow[0];
    $quotedTable = $quoteIdentifier($table);
    $createTable = $pdo
        ->query('SHOW CREATE TABLE '.$quotedTable)
        ->fetch(PDO::FETCH_ASSOC)['Create Table'];

    $lines[] = 'DROP TABLE IF EXISTS '.$quotedTable.';';
    $lines[] = $createTable.';';
    $lines[] = '';

    $rows = $pdo->query('SELECT * FROM '.$quotedTable)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $columns = array_map($quoteIdentifier, array_keys($row));
        $values = array_map($quoteValue, array_values($row));

        $lines[] = 'INSERT INTO '.$quotedTable.' ('.implode(', ', $columns).') VALUES ('.implode(', ', $values).');';
    }

    if ($rows !== []) {
        $lines[] = '';
    }
}

$lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
$lines[] = '';

file_put_contents($exportPath, implode(PHP_EOL, $lines));

echo 'Database exported to '.$exportPath.PHP_EOL;
