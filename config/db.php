<?php
$host = 'localhost';
$dbname = 'pwapractica5';
$user = 'root';
$pass = 'DeltaUBE2026';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die('Error de conexión a la base de datos: ' . $e->getMessage());
}
