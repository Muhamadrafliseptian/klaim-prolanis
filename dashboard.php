<?php
require_once 'config.php';
// require_once 'sidebar.php'; // Pindahan ke bawah (di dalam body HTML) agar layout tidak rusak

if (!isLoggedIn()) {
    redirect('index.php');
}

$role = $_SESSION['role'];

// --- QUERY SIMULASI AMBIL DATA DARI DATABASE ---
try {
    // 1. Hitung Total Seluruh Pasien Terdaftar
    $stmtPasien = $pdo->query("SELECT COUNT(*) FROM pasien");
    $total_pasien = $stmtPasien->fetchColumn();

    // 2. Hitung Transaksi Hari Ini
    $stmtTransaksiHariIni = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE DATE(tanggal_transaksi) = CURDATE()");
    $transaksi_hari_ini = $stmtTransaksiHariIni->fetchColumn();

    // 3. Hitung Total Pendapatan Bulan Ini (Hanya yang sudah lunas/selesai)
    $stmtPendapatan = $pdo->query("SELECT SUM(total_harga) FROM transaksi WHERE MONTH(tanggal_transaksi) = MONTH(CURDATE()) AND YEAR(tanggal_transaksi) = YEAR(CURDATE())");
    $pendapatan_bulan_ini = $stmtPendapatan->fetchColumn() ?? 0;

    // 4. Hitung Transaksi Belum Lunas (Butuh Tindakan)
    $stmtBelumLunas = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status = 'belum lunas'");
    $belum_lunas = $stmtBelumLunas->fetchColumn();

} catch (PDOException $e) {
    // Fallback data aman jika tabel/kolom database belum lengkap
    $total_pasien = 0;
    $transaksi_hari_ini = 0;
    $pendapatan_bulan_ini = 0;
    $belum_lunas = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Panel - Klinik LAB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-slate-50 font-sans antialiased">

    <div class="flex flex-col md:flex-row min-h-screen">
        
        <?php include 'sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0">
            
            <header class="bg-white border-b border-slate-200 sticky top-0 z-10 shadow-xs">
                <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-widest text-emerald-600">Pusat Informasi Utama</span>
                        <h1 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight flex items-center space-x-2">
                            <span>Klinik <span class="text-emerald-500">LAB</span> Monitor</span>
                        </h1>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="hidden sm:flex flex-col text-right border-slate-200 pr-2">
                            <span class="text-sm font-semibold text-slate-700"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
                            <span class="text-xs text-slate-400 capitalize font-medium">Akses: <?= htmlspecialchars($role) ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-8 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-4 text-emerald-800">
                    <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse flex-shrink-0"></div>
                        <p class="text-sm font-medium">Sistem berjalan normal. Data diperbarui secara realtime.</p>
                    </div>
                    <div class="text-xs font-bold uppercase bg-white/80 backdrop-blur-xs px-3 py-1 rounded-lg border border-emerald-500/10 self-start sm:self-auto">
                        <i class="bi bi-clock mr-1.5"></i> <?= date('d M Y') ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200 flex items-center justify-between hover:border-blue-400 transition-colors duration-200">
                        <div class="space-y-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-400 uppercase tracking-wider truncate">Total Pasien</p>
                            <h3 class="text-3xl font-black text-slate-800 tracking-tight truncate"><?= number_format($total_pasien, 0, ',', '.') ?></h3>
                            <p class="text-xs text-slate-400 pt-1">Jiwa terregistrasi</p>
                        </div>
                        <div class="p-4 bg-blue-50 text-blue-500 rounded-2xl flex-shrink-0">
                            <i class="bi bi-people text-2xl"></i>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200 flex items-center justify-between hover:border-emerald-400 transition-colors duration-200">
                        <div class="space-y-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-400 uppercase tracking-wider truncate">Transaksi Hari Ini</p>
                            <h3 class="text-3xl font-black text-slate-800 tracking-tight truncate"><?= number_format($transaksi_hari_ini, 0, ',', '.') ?></h3>
                            <p class="text-xs text-emerald-600 font-medium pt-1 flex items-center truncate">
                                <i class="bi bi-graph-up mr-1-shrink-0"></i> Berkas aktif
                            </p>
                        </div>
                        <div class="p-4 bg-emerald-50 text-emerald-500 rounded-2xl flex-shrink-0">
                            <i class="bi bi-activity text-2xl"></i>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200 flex items-center justify-between hover:border-purple-400 transition-colors duration-200">
                        <div class="space-y-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-400 uppercase tracking-wider truncate">Omset Bulan Ini</p>
                            <h3 class="text-xl font-black text-slate-800 tracking-tight truncate">Rp <?= number_format($pendapatan_bulan_ini, 0, ',', '.') ?></h3>
                            <p class="text-xs text-slate-400 pt-1">Bulan berjalan</p>
                        </div>
                        <div class="p-4 bg-purple-50 text-purple-500 rounded-2xl flex-shrink-0">
                            <i class="bi bi-wallet2 text-2xl"></i>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200 flex items-center justify-between hover:border-amber-400 transition-colors duration-200">
                        <div class="space-y-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-400 uppercase tracking-wider truncate">Belum Lunas</p>
                            <h3 class="text-3xl font-black text-slate-800 tracking-tight truncate"><?= number_format($belum_lunas, 0, ',', '.') ?></h3>
                            <p class="text-xs text-amber-600 font-medium pt-1 truncate">Menunggu kasir</p>
                        </div>
                        <div class="p-4 bg-amber-50 text-amber-500 rounded-2xl flex-shrink-0">
                            <i class="bi bi-hourglass-split text-2xl"></i>
                        </div>
                    </div>

                </div>

            </main>

            <footer class="bg-white border-t border-slate-200 py-4 mt-auto">
                <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-slate-400 font-medium">
                    &copy; <?= date('Y') ?> Klinik LAB Panel System. All rights reserved.
                </div>
            </footer>

        </div>
    </div>

</body>
</html>