<?php
$host = "ep-sweet-lab-agup0us7-pooler.c-2.eu-central-1.aws.neon.tech";
$db = "neondb";
$user = "neondb_owner";
$pass = "npg_JTg4Hx8OQnoe";

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