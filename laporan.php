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
$total_lunas = count(array_filter($transaksi, function($t) { return $t['status'] == 'lunas'; }));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS untuk print -->
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .sidebar-print-hide {
                display: none !important;
            }
            .main-content-print {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
            .btn-print-hide {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 ml-0 transition-all duration-300">
            <div class="p-6">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Laporan Keuangan</h1>
                    <p class="text-gray-600">Periode: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></p>
                </div>
                
                <!-- Filter Form -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6 no-print">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Dari Tanggal</label>
                            <input type="date" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" value="<?= $start_date ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" value="<?= $end_date ?>">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="bi bi-search me-2"></i>Filter
                            </button>
                        </div>
                        <div class="flex items-end">
                            <button type="button" onclick="window.print()" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                                <i class="bi bi-printer me-2"></i>Cetak
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Statistik Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm uppercase tracking-wide">Total Pendapatan</p>
                                <p class="text-3xl font-bold mt-2">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></p>
                            </div>
                            <i class="bi bi-currency-dollar text-5xl opacity-50"></i>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm uppercase tracking-wide">Total Transaksi</p>
                                <p class="text-3xl font-bold mt-2"><?= $total_transaksi ?></p>
                            </div>
                            <i class="bi bi-receipt text-5xl opacity-50"></i>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100 text-sm uppercase tracking-wide">Transaksi Lunas</p>
                                <p class="text-3xl font-bold mt-2"><?= $total_lunas ?></p>
                            </div>
                            <i class="bi bi-check-circle text-5xl opacity-50"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Tabel Transaksi -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-800 text-white">
                                <tr>
                                    <th class="px-6 py-3 text-left text-sm font-semibold">No. Invoice</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold">Pasien</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold">Tanggal</th>
                                    <th class="px-6 py-3 text-right text-sm font-semibold">Total</th>
                                    <th class="px-6 py-3 text-center text-sm font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if(count($transaksi) > 0): ?>
                                    <?php foreach($transaksi as $trx): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($trx['no_invoice']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($trx['nama_pasien']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-600"><?= date('d/m/Y H:i', strtotime($trx['tanggal_transaksi'])) ?></td>
                                        <td class="px-6 py-4 text-sm text-right font-medium text-gray-900">Rp <?= number_format($trx['total_harga'], 0, ',', '.') ?></td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if($trx['status'] == 'lunas'): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="bi bi-check-circle-fill me-1 text-xs"></i> Lunas
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <i class="bi bi-clock-fill me-1 text-xs"></i> <?= ucfirst($trx['status']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                            <i class="bi bi-inbox text-4xl mb-2 block"></i>
                                            Tidak ada data transaksi untuk periode ini
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if(count($transaksi) > 0): ?>
                            <tfoot class="bg-gray-50 font-semibold">
                                <tr>
                                    <td colspan="3" class="px-6 py-3 text-right text-sm">Total Keseluruhan:</td>
                                    <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
                                    <td class="px-6 py-3"></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
                
                <!-- Tombol Aksi -->
                <div class="mt-6 flex gap-3 no-print">
                    <a href="dashboard.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="bi bi-arrow-left me-2"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>