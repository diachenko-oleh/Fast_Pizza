<?php
require_once __DIR__ . '/../View/config.php';
$host = "ep-sweet-lab-agup0us7-pooler.c-2.eu-central-1.aws.neon.tech";
$db = "neondb";
$user = "neondb_owner";
$pass = DB_PASS;

try {
    $pdo = new PDO(
        "pgsql:host=$host;dbname=$db;sslmode=require",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}