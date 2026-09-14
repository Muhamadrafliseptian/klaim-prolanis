<?php
require_once 'config.php';

// Proteksi Keamanan: Hanya Bendahara yang boleh melakukan persetujuan
if (!isLoggedIn() || !isRole('bendahara')) {
    die("Akses ditolak! Anda tidak memiliki otoritas.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['konfirmasi_setuju'])) {
    $transaksi_id = $_POST['transaksi_id'];

    if (!empty($transaksi_id)) {
        // Update status menjadi 'disetujui' dan perbarui timestamp log
        $stmt = $pdo->prepare("UPDATE transaksi SET status = 'disetujui', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$transaksi_id]);

        // Kembalikan ke halaman invoice dengan pesan sukses (sesuaikan nama file invoice Anda)
        header("Location: invoice.php?id=" . $transaksi_id . "&msg=success");
        exit();
    }
}

// Jika diakses tidak sah, kembalikan ke dashboard
redirect('dashboard.php');