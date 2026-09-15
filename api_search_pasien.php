<?php
require_once 'config.php';

if (!isLoggedIn()) {
    echo json_encode([]);
    exit;
}

header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    if (!empty($q)) {
        // Ambil maksimal 20 data saja agar sangat cepat
        $stmt = $pdo->prepare("SELECT id, nama, nik FROM pasien WHERE nama LIKE ? OR nik LIKE ? ORDER BY nama ASC LIMIT 20");
        $stmt->execute(["%$q%", "%$q%"]);
    } else {
        // Tampilkan 10 data pertama jika kolom pencarian kosong
        $stmt = $pdo->query("SELECT id, nama, nik FROM pasien ORDER BY nama ASC LIMIT 10");
    }
    
    $pasien = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($pasien);
} catch (PDOException $e) {
    echo json_encode([]);
}