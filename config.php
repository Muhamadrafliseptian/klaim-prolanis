<?php
session_start();

$host = '127.0.0.1';
$port = '3306';
$dbname = 'klaim_prolanis';
$username = 'hris_user';
$password = 'Hris@2026';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] == $role;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function generateInvoice() {
    return 'INV/' . date('Ymd') . '/' . rand(1000, 9999);
}
?>