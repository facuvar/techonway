<?php
// Script de importación para Railway
echo 'Importando datos a Railway...';
require_once 'config/database.php';
$config = require 'config/database.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4", $config['username'], $config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sql = file_get_contents('railway_sync_2025-08-15_14-42-36.sql');
$pdo->exec($sql);
echo 'Importación completada!';
