<?php
$host = 'sql108.infinityfree.com';
$dbname = 'if0_40541015_finalregistronotas';
$username = 'if0_40541015';
$password = 'GELaME7Nv8';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>