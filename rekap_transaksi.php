<?php
    
// Langsung test koneksi tanpa require dulu
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Cek login
if (!isLoggedIn()) {
    redirect('login.php');
}

// Inisialisasi filter
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$status = isset($_GET['status']) ? $_GET['status'] : '';
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';

// Cek struktur tabel pasien terlebih dahulu
try {
    $stmt = $pdo->query("DESCRIBE pasien");
    $pasien_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Tentukan kolom yang tersedia
    $kolom_nama = in_array('nama', $pasien_columns) ? 'nama' : (in_array('nama_pasien', $pasien_columns) ? 'nama_pasien' : 'id');
    $kolom_rm = in_array('no_rm', $pasien_columns) ? 'no_rm' : (in_array('no_rekam_medis', $pasien_columns) ? 'no_rekam_medis' : (in_array('rm', $pasien_columns) ? 'rm' : 'id'));
    
} catch(PDOException $e) {
    // Jika tabel pasien tidak ada, lanjutkan tanpa JOIN
    $kolom_nama = 'id';
    $kolom_rm = 'id';
}

// Query dasar dengan JOIN ke tabel pasien (gunakan kolom yang benar)
$sql = "SELECT t.id, t.no_invoice, t.pasien_id, t.tanggal_transaksi, 
        t.total_harga, t.status, t.created_by, t.created_at, t.updated_at";

// Tambahkan kolom pasien jika tabel ada
try {
    $check_table = $pdo->query("SHOW TABLES LIKE 'pasien'");
    if ($check_table->rowCount() > 0) {
        $sql .= ", p.$kolom_nama as nama_pasien, p.$kolom_rm as no_rekam_medis";
        $sql .= " FROM transaksi t LEFT JOIN pasien p ON t.pasien_id = p.id";
    } else {
        $sql .= " FROM transaksi t";
    }
} catch(PDOException $e) {
    $sql .= " FROM transaksi t";
}

$sql .= " WHERE 1=1";
$params = [];

// Tambahkan filter tanggal
if ($tanggal_awal && $tanggal_akhir) {
    $sql .= " AND DATE(t.tanggal_transaksi) BETWEEN :tanggal_awal AND :tanggal_akhir";
    $params[':tanggal_awal'] = $tanggal_awal;
    $params[':tanggal_akhir'] = $tanggal_akhir;
}

// Tambahkan filter status
if ($status != '') {
    $sql .= " AND t.status = :status";
    $params[':status'] = $status;
}

// Tambahkan pencarian (hanya jika kolom tersedia)
if ($cari != '') {
    $sql .= " AND (t.no_invoice LIKE :cari";
    if (isset($kolom_nama) && $kolom_nama != 'id') {
        $sql .= " OR p.$kolom_nama LIKE :cari";
    }
    if (isset($kolom_rm) && $kolom_rm != 'id') {
        $sql .= " OR p.$kolom_rm LIKE :cari";
    }
    $sql .= ")";
    $params[':cari'] = "%$cari%";
}

$sql .= " ORDER BY t.tanggal_transaksi DESC";

// Eksekusi query
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error query: " . $e->getMessage() . "<br>SQL: " . $sql);
}

// Hitung total keseluruhan
$total_keseluruhan = 0;
foreach ($transaksi as $row) {
    $total_keseluruhan += isset($row['total_harga']) ? $row['total_harga'] : 0;
}

// Hitung statistik per status
$statistik = [
    'lunas' => 0,
    'pending' => 0,
    'batal' => 0
];

foreach ($transaksi as $row) {
    $status_key = isset($row['status']) ? $row['status'] : '';
    if (isset($statistik[$status_key])) {
        $statistik[$status_key]++;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Transaksi - Klaim PTM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        /* Card Statistik */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
        }
        
        .stat-card.total .number { color: #667eea; }
        .stat-card.lunas .number { color: #4CAF50; }
        .stat-card.pending .number { color: #ff9800; }
        .stat-card.batal .number { color: #f44336; }
        
        /* Filter Form */
        .filter-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-success {
            background: #4CAF50;
            color: white;
        }
        
        .btn-success:hover {
            background: #45a049;
        }
        
        .btn-warning {
            background: #ff9800;
            color: white;
        }
        
        /* Tabel */
        .table-container {
            background: white;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-lunas {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-batal {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Footer Total */
        .footer-total {
            margin-top: 20px;
            padding: 15px 20px;
            background: #e8f5e9;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .total-amount {
            font-size: 20px;
            font-weight: bold;
            color: #2e7d32;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        /* Print styles */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .filter-card, .stats, .action-buttons, .btn, .no-print {
                display: none;
            }
            .header {
                background: #333;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table {
                font-size: 12px;
            }
            th {
                background: #ddd;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
            }
            .filter-group {
                width: 100%;
            }
            .action-buttons {
                width: 100%;
                justify-content: stretch;
            }
            .action-buttons .btn {
                flex: 1;
            }
        }
        
        .text-right {
            text-align: right;
        }
        
        .mb-2 {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <!-- Header -->
        <div class="header">
            <h1>📊 Rekap Transaksi</h1>
            <p>Laporan lengkap semua transaksi klaim BPJS PTM</p>
        </div>
        
        <!-- Statistik -->
        <div class="stats">
            <div class="stat-card total">
                <h3>Total Transaksi</h3>
                <div class="number"><?php echo count($transaksi); ?></div>
            </div>
            <div class="stat-card lunas">
                <h3>✅ Lunas</h3>
                <div class="number"><?php echo $statistik['lunas']; ?></div>
            </div>
            <div class="stat-card pending">
                <h3>⏳ Pending</h3>
                <div class="number"><?php echo $statistik['pending']; ?></div>
            </div>
            <div class="stat-card batal">
                <h3>❌ Batal</h3>
                <div class="number"><?php echo $statistik['batal']; ?></div>
            </div>
        </div>
        
        <!-- Filter Form -->
        <div class="filter-card">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label>📅 Tanggal Awal</label>
                    <input type="date" name="tanggal_awal" value="<?php echo $tanggal_awal; ?>">
                </div>
                <div class="filter-group">
                    <label>📅 Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" value="<?php echo $tanggal_akhir; ?>">
                </div>
                <div class="filter-group">
                    <label>🔍 Status</label>
                    <select name="status">
                        <option value="">Semua Status</option>
                        <option value="lunas" <?php echo $status == 'lunas' ? 'selected' : ''; ?>>Lunas</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="batal" <?php echo $status == 'batal' ? 'selected' : ''; ?>>Batal</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>🔎 Cari</label>
                    <input type="text" name="cari" placeholder="No. Invoice / Pasien / RM" value="<?php echo htmlspecialchars($cari); ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">🔍 Filter</button>
                    <button type="button" onclick="window.location.href='rekap_transaksi.php'" class="btn btn-secondary">↺ Reset</button>
                </div>
            </form>
        </div>
        
        <!-- Tombol Aksi -->
        <div class="action-buttons no-print" style="margin-bottom: 15px;">
            <button onclick="window.print()" class="btn btn-primary">🖨️ Cetak Laporan</button>
            <button onclick="exportToExcel()" class="btn btn-success">📊 Export Excel</button>
            <button onclick="window.open('cetak_laporan.php?<?php echo http_build_query($_GET); ?>', '_blank')" class="btn btn-warning">📄 Cetak Detail</button>
        </div>
        
        <!-- Tabel Data -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No. Invoice</th>
                        <th>No. RM</th>
                        <th>Nama Pasien</th>
                        <th>Tanggal Transaksi</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                        <th>Dibuat Oleh</th>
                        <th>Waktu Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transaksi) > 0): ?>
                        <?php $no = 1; foreach ($transaksi as $row): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($row['no_invoice'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['no_rekam_medis'] ?? $row['no_rm'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_pasien'] ?? $row['nama'] ?? '-'); ?></td>
                                <td><?php echo isset($row['tanggal_transaksi']) ? date('d/m/Y', strtotime($row['tanggal_transaksi'])) : '-'; ?></td>
                                <td class="text-right">Rp <?php echo number_format($row['total_harga'] ?? 0, 0, ',', '.'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status'] ?? ''; ?>">
                                        <?php 
                                        $status_text = [
                                            'lunas' => '✅ Lunas',
                                            'pending' => '⏳ Pending',
                                            'batal' => '❌ Batal'
                                        ];
                                        echo $status_text[$row['status'] ?? ''] ?? ($row['status'] ?? '-');
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['created_by'] ?? '-'); ?></td>
                                <td><?php echo isset($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                📭 Tidak ada data transaksi untuk periode yang dipilih
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Footer Total -->
        <div class="footer-total">
            <div>
                <strong>Total Transaksi:</strong> <?php echo count($transaksi); ?> transaksi
                <br>
                <small>Periode: <?php echo date('d/m/Y', strtotime($tanggal_awal)); ?> s/d <?php echo date('d/m/Y', strtotime($tanggal_akhir)); ?></small>
            </div>
            <div>
                <div class="total-amount">
                    💰 Total Pendapatan: Rp <?php echo number_format($total_keseluruhan, 0, ',', '.'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function exportToExcel() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = 'export_excel.php?' + params.toString();
        }
    </script>
</body>
</html>