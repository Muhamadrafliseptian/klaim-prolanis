<?php
require_once 'config.php';

if (!isLoggedIn() || !isRole('admin')) {
    redirect('login.php');
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT t.*, p.nama as nama_pasien 
    FROM transaksi t
    JOIN pasien p ON t.pasien_id = p.id
    WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
    ORDER BY t.tanggal_transaksi DESC
");
$stmt->execute([$start_date, $end_date]);
$transaksi = $stmt->fetchAll();

$total_pendapatan = array_sum(array_column($transaksi, 'total_harga'));
$total_transaksi = count($transaksi);
// Menghitung status disetujui atau lunas
$total_lunas = count(array_filter($transaksi, function($t) { 
    $s = strtolower($t['status']);
    return $s == 'lunas' || $s == 'disetujui'; 
}));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - Klinik LAB</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background-color: #ffffff !important; }
            main { padding: 0 !important; }
            .print-border { border: 1px solid #e2e8f0 !important; border-radius: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased">

    <div class="flex flex-col md:flex-row min-h-screen">
        
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 p-4 md:p-6 space-y-4 min-w-0">
            
            <!-- Breadcrumb & Header -->
            <div class="flex items-center justify-between flex-wrap gap-2 no-print">
                <div class="flex items-center space-x-2 text-xs text-slate-400">
                    <a href="dashboard.php" class="hover:text-emerald-500">Dashboard</a>
                    <span>/</span>
                    <span class="text-slate-600 font-medium">Laporan Keuangan</span>
                </div>
                
                <button type="button" onclick="window.print()" 
                        class="px-3 py-1.5 bg-slate-800 text-white text-xs font-semibold rounded-lg hover:bg-slate-700 transition-all flex items-center space-x-1.5 shadow-xs">
                    <i class="bi bi-printer"></i>
                    <span>Cetak Laporan</span>
                </button>
            </div>

            <!-- Header Cetak Rekap (Hanya Tampil Saat Print) -->
            <div class="hidden print:block mb-4 text-center border-b border-slate-300 pb-3">
                <h1 class="text-xl font-bold text-slate-900">LAPORAN KEUANGAN LABORATORIUM</h1>
                <p class="text-xs text-slate-600">Periode: <?= date('d/m/Y', strtotime($start_date)) ?> s/d <?= date('d/m/Y', strtotime($end_date)) ?></p>
            </div>

            <!-- Filter Tanggal -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 no-print">
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 gap-3 items-end">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Dari Tanggal</label>
                        <input type="date" name="start_date" value="<?= $start_date ?>" 
                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:bg-white focus:outline-hidden focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="<?= $end_date ?>" 
                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:bg-white focus:outline-hidden focus:border-emerald-500">
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="flex-1 py-1.5 bg-emerald-500 text-white text-xs font-semibold rounded-lg hover:bg-emerald-600 transition-colors flex items-center justify-center space-x-1">
                            <i class="bi bi-filter"></i>
                            <span>Filter</span>
                        </button>
                        <a href="laporan.php" class="px-3 py-1.5 bg-slate-100 text-slate-600 text-xs font-semibold rounded-lg hover:bg-slate-200 transition-colors flex items-center justify-center">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Cards Ringkasan -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between print-border">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Pendapatan</p>
                        <h3 class="text-lg font-black text-emerald-600 mt-0.5">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></h3>
                    </div>
                    <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-lg no-print">
                        <i class="bi bi-wallet2 text-xl"></i>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between print-border">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Transaksi</p>
                        <h3 class="text-lg font-black text-slate-800 mt-0.5"><?= number_format($total_transaksi, 0, ',', '.') ?></h3>
                    </div>
                    <div class="p-2.5 bg-blue-50 text-blue-600 rounded-lg no-print">
                        <i class="bi bi-receipt text-xl"></i>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between print-border">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Disetujui / Lunas</p>
                        <h3 class="text-lg font-black text-indigo-600 mt-0.5"><?= number_format($total_lunas, 0, ',', '.') ?></h3>
                    </div>
                    <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-lg no-print">
                        <i class="bi bi-check-circle text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Tabel Data Mode Terang -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden print-border">
                
                <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 bg-white no-print">
                    <div class="flex items-center space-x-2">
                        <i class="bi bi-bar-chart-line text-emerald-600 text-lg"></i>
                        <h2 class="text-sm font-bold text-slate-800">Rincian Transaksi</h2>
                    </div>
                    
                    <div class="relative w-full sm:w-56">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                            <i class="bi bi-search text-xs"></i>
                        </span>
                        <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Cari invoice/pasien..." 
                               class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:bg-white focus:outline-hidden focus:border-emerald-500">
                    </div>
                </div>

                <div class="w-full">
                    <table class="w-full text-xs text-left text-slate-600">
                        <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 uppercase">
                            <tr>
                                <th class="px-3 py-2.5 w-32">No. Invoice</th>
                                <th class="px-3 py-2.5">Nama Pasien</th>
                                <th class="px-3 py-2.5 w-36">Tanggal Periksa</th>
                                <th class="px-3 py-2.5 w-28">Total</th>
                                <th class="px-3 py-2.5 w-24 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody" class="divide-y divide-slate-100">
                            <?php if(count($transaksi) > 0): ?>
                                <?php foreach($transaksi as $trx): ?>
                                <tr class="table-row hover:bg-slate-50 transition-colors">
                                    <td class="px-3 py-2.5 font-mono font-bold text-slate-800">
                                        <?= htmlspecialchars($trx['no_invoice']) ?>
                                    </td>
                                    <td class="px-3 py-2.5 font-semibold text-slate-800">
                                        <?= htmlspecialchars($trx['nama_pasien']) ?>
                                    </td>
                                    <td class="px-3 py-2.5 text-slate-500">
                                        <?= date('d/m/Y H:i', strtotime($trx['tanggal_transaksi'])) ?>
                                    </td>
                                    <td class="px-3 py-2.5 font-bold text-slate-900">
                                        Rp <?= number_format($trx['total_harga'], 0, ',', '.') ?>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <?php 
                                        $st = strtolower($trx['status']);
                                        if($st == 'disetujui' || $st == 'lunas'): 
                                        ?>
                                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-md text-[10px] font-bold uppercase">
                                                <?= htmlspecialchars($trx['status']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-md text-[10px] font-bold uppercase">
                                                <?= htmlspecialchars($trx['status']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-3 py-8 text-center text-slate-400">
                                        Tidak ada data transaksi pada periode ini.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if(count($transaksi) > 0): ?>
                        <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                            <tr>
                                <td colspan="3" class="px-3 py-2.5 text-right text-slate-700">Total Keseluruhan:</td>
                                <td class="px-3 py-2.5 text-emerald-600 text-sm">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>

                    <!-- Pagination Ringkas -->
                    <div class="p-3 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500 bg-white no-print">
                        <div>
                            Menampilkan <span id="pageInfo" class="font-bold text-slate-800">0 - 0</span> dari <span class="font-bold text-slate-800"><?= count($transaksi) ?></span>
                        </div>
                        <div class="flex items-center space-x-1" id="paginationControls"></div>
                    </div>

                </div>
            </div>

            <div class="pt-2 no-print">
                <a href="dashboard.php" class="px-3 py-1.5 bg-slate-100 text-slate-600 text-xs font-semibold rounded-lg hover:bg-slate-200 transition-colors inline-flex items-center space-x-1">
                    <i class="bi bi-arrow-left"></i>
                    <span>Kembali ke Dashboard</span>
                </a>
            </div>

        </main>
    </div>

    <script>
        const rowsPerPage = 10;
        let currentPage = 1;
        const allRows = Array.from(document.querySelectorAll('.table-row'));
        let filteredRows = [...allRows];

        function renderTable() {
            if (allRows.length === 0) return;

            const totalPages = Math.ceil(filteredRows.length / rowsPerPage) || 1;
            if (currentPage > totalPages) currentPage = totalPages;

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            allRows.forEach(row => row.style.display = 'none');
            filteredRows.slice(start, end).forEach(row => row.style.display = '');

            const countStart = filteredRows.length > 0 ? start + 1 : 0;
            const countEnd = Math.min(end, filteredRows.length);
            document.getElementById('pageInfo').innerText = `${countStart} - ${countEnd}`;

            // --- RENDER PAGINATION RINGKAS (MAX 5 TOMBOL) ---
            let paginationHTML = '';
            
            paginationHTML += `<button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="px-2 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 disabled:opacity-40 text-xs">Prev</button>`;

            let startPage = Math.max(1, currentPage - 1);
            let endPage = Math.min(totalPages, currentPage + 1);

            if (currentPage === 1) {
                endPage = Math.min(totalPages, 3);
            } else if (currentPage === totalPages) {
                startPage = Math.max(1, totalPages - 2);
            }

            if (startPage > 1) {
                paginationHTML += `<button onclick="changePage(1)" class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 font-semibold text-xs">1</button>`;
                if (startPage > 2) {
                    paginationHTML += `<span class="px-1 text-slate-400 text-xs">...</span>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    paginationHTML += `<button class="px-2.5 py-1 rounded bg-emerald-500 text-white font-bold text-xs">${i}</button>`;
                } else {
                    paginationHTML += `<button onclick="changePage(${i})" class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 font-semibold text-xs">${i}</button>`;
                }
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    paginationHTML += `<span class="px-1 text-slate-400 text-xs">...</span>`;
                }
                paginationHTML += `<button onclick="changePage(${totalPages})" class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 font-semibold text-xs">${totalPages}</button>`;
            }

            paginationHTML += `<button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} class="px-2 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 disabled:opacity-40 text-xs">Next</button>`;
            
            document.getElementById('paginationControls').innerHTML = paginationHTML;
        }

        function changePage(page) {
            const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            renderTable();
        }

        function searchTable() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            filteredRows = allRows.filter(row => row.innerText.toLowerCase().includes(query));
            currentPage = 1;
            renderTable();
        }

        renderTable();
    </script>
</body>
</html>