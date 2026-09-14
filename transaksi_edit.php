<?php
ob_start();
date_default_timezone_set('Asia/Jakarta');
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Ambil ID transaksi dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    redirect('transaksi_list.php');
}

// Ambil data transaksi yang akan diedit
try {
    $stmt = $pdo->prepare("
        SELECT t.*, p.nama as nama_pasien, p.nik, p.no_bpjs, p.alamat
        FROM transaksi t
        JOIN pasien p ON t.pasien_id = p.id
        WHERE t.id = ?
    ");
    $stmt->execute([$id]);
    $transaksi = $stmt->fetch();
    
    if (!$transaksi) {
        redirect('transaksi_list.php');
    }
    
    // Ambil detail transaksi (pemeriksaan yang dipilih)
    $stmt_detail = $pdo->prepare("
        SELECT dt.*, jp.nama_pemeriksaan, jp.harga as harga_asli
        FROM detail_transaksi dt
        JOIN jenis_pemeriksaan jp ON dt.pemeriksaan_id = jp.id
        WHERE dt.transaksi_id = ?
    ");
    $stmt_detail->execute([$id]);
    $detail_transaksi = $stmt_detail->fetchAll();
    
    // Array ID pemeriksaan yang sudah dipilih
    $selected_pemeriksaan = array_column($detail_transaksi, 'pemeriksaan_id');
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Ambil list pasien & pemeriksaan
$pasien_list = $pdo->query("SELECT * FROM pasien ORDER BY nama ASC")->fetchAll();
$pemeriksaan_list = $pdo->query("SELECT * FROM jenis_pemeriksaan ORDER BY nama_pemeriksaan ASC")->fetchAll();

$error = '';
$success = '';

// Proses update data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_transaksi'])) {
    try {
        $pasien_id = $_POST['pasien_id'];
        $tgl_invoice = $_POST['tgl_invoice'];
        $tanggal_transaksi = $_POST['tanggal_transaksi'];
        $status = $_POST['status'];
        
        if (empty($tgl_invoice)) {
            throw new Exception("Tanggal invoice harus diisi.");
        }
        
        if (!isset($_POST['pemeriksaan']) || empty($_POST['pemeriksaan'])) {
            throw new Exception("Silakan pilih minimal satu jenis pemeriksaan.");
        }
        
        $pemeriksaan_ids = $_POST['pemeriksaan'];
        $total = 0;
        $detail_tarif = [];

        $pdo->beginTransaction();
        
        // Hitung ulang total harga berdasarkan pemeriksaan yang dipilih
        $placeholders = implode(',', array_fill(0, count($pemeriksaan_ids), '?'));
        $stmt = $pdo->prepare("SELECT id, harga FROM jenis_pemeriksaan WHERE id IN ($placeholders)");
        $stmt->execute($pemeriksaan_ids);
        $harga_items = $stmt->fetchAll();
        
        foreach ($harga_items as $item) {
            $detail_tarif[$item['id']] = $item['harga'];
            $total += $item['harga'];
        }
        
        // Update transaksi utama
        $stmt = $pdo->prepare("
            UPDATE transaksi 
            SET pasien_id = ?, 
                tgl_invoice = ?, 
                tanggal_transaksi = ?, 
                total_harga = ?, 
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$pasien_id, $tgl_invoice, $tanggal_transaksi, $total, $status, $id]);
        
        // Hapus detail transaksi lama
        $stmt_delete = $pdo->prepare("DELETE FROM detail_transaksi WHERE transaksi_id = ?");
        $stmt_delete->execute([$id]);
        
        // Insert detail transaksi baru
        $stmt_detail = $pdo->prepare("INSERT INTO detail_transaksi (transaksi_id, pemeriksaan_id, harga) VALUES (?, ?, ?)");
        foreach ($pemeriksaan_ids as $pid) {
            $harga_layanan = isset($detail_tarif[$pid]) ? $detail_tarif[$pid] : 0;
            $stmt_detail->execute([$id, $pid, $harga_layanan]);
        }
        
        $pdo->commit();
        
        $success = "Transaksi berhasil diperbarui!";
        
        // Refresh data setelah update
        $stmt = $pdo->prepare("
            SELECT t.*, p.nama as nama_pasien, p.nik, p.no_bpjs, p.alamat
            FROM transaksi t
            JOIN pasien p ON t.pasien_id = p.id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        $transaksi = $stmt->fetch();
        
        $stmt_detail = $pdo->prepare("
            SELECT dt.*, jp.nama_pemeriksaan, jp.harga as harga_asli
            FROM detail_transaksi dt
            JOIN jenis_pemeriksaan jp ON dt.pemeriksaan_id = jp.id
            WHERE dt.transaksi_id = ?
        ");
        $stmt_detail->execute([$id]);
        $detail_transaksi = $stmt_detail->fetchAll();
        $selected_pemeriksaan = array_column($detail_transaksi, 'pemeriksaan_id');
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Proses pilih pasien dari modal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pilih_pasien'])) {
    $pasien_id = $_POST['pasien_id'];
    $stmt = $pdo->prepare("SELECT * FROM pasien WHERE id = ?");
    $stmt->execute([$pasien_id]);
    $selected_pasien = $stmt->fetch();
    
    // Update pasien di transaksi
    $stmt = $pdo->prepare("UPDATE transaksi SET pasien_id = ? WHERE id = ?");
    $stmt->execute([$pasien_id, $id]);
    
    // Refresh data
    redirect("transaksi_edit.php?id=$id");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaksi - Klinik LAB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-slate-50 font-sans antialiased">

    <div class="flex flex-col md:flex-row min-h-screen">
        
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 p-6 md:p-8 space-y-6 min-w-0">
            
            <div class="flex items-center space-x-2 text-sm text-slate-400">
                <a href="dashboard.php" class="hover:text-emerald-500 transition-colors">Dashboard</a>
                <span>/</span>
                <a href="transaksi_list.php" class="hover:text-emerald-500 transition-colors">Daftar Transaksi</a>
                <span>/</span>
                <span class="text-slate-600 font-medium">Edit Transaksi</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mx-auto">
                
                <div class="bg-gradient-to-r from-amber-500 to-amber-600 p-5 text-white flex items-center space-x-3">
                    <div class="p-2 bg-white/20 text-white border border-white/20 rounded-xl">
                        <i class="bi bi-pencil-square text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold tracking-wide">Edit Transaksi</h2>
                        <p class="text-xs text-white/80">Invoice: <?= htmlspecialchars($transaksi['no_invoice']) ?></p>
                    </div>
                </div>

                <div class="p-6 md:p-8 space-y-6">

                    <?php if($error): ?>
                        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center space-x-3 text-sm">
                            <i class="bi bi-exclamation-triangle-fill text-rose-500 text-lg"></i>
                            <span class="font-medium"><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if($success): ?>
                        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center space-x-3 text-sm">
                            <i class="bi bi-check-circle-fill text-emerald-500 text-lg"></i>
                            <span class="font-medium"><?= htmlspecialchars($success) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pasien Rekam Medis</label>
                        <div class="flex space-x-3">
                            <div class="flex-1 px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200 text-sm text-slate-700 flex items-center justify-between">
                                <span class="font-medium">
                                    <?= htmlspecialchars($transaksi['nama_pasien']) ?> — [<?= htmlspecialchars($transaksi['nik']) ?>]
                                </span>
                                <span class="text-xs px-2 py-0.5 bg-emerald-100 text-emerald-800 font-bold rounded-md">Aktif</span>
                            </div>
                            <button type="button" onclick="openModal()" 
                                    class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-800 transition-colors flex items-center space-x-2 shrink-0">
                                <i class="bi bi-search"></i>
                                <span>Ganti Pasien</span>
                            </button>
                        </div>
                    </div>

                    <form method="POST" class="border-t border-slate-100 pt-6">
                        <input type="hidden" name="pasien_id" value="<?= $transaksi['pasien_id'] ?>">

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                            
                            <div class="lg:col-span-5 space-y-5">
                                
                                <div class="p-5 bg-white border border-slate-200 rounded-2xl space-y-3 shadow-xs">
                                    <label for="tgl_invoice" class="text-xs font-bold uppercase text-slate-500 tracking-wider flex items-center space-x-1.5">
                                        <i class="bi bi-calendar-week text-amber-500"></i>
                                        <span>Tanggal Invoice</span>
                                    </label>
                                    <input type="date" name="tgl_invoice" id="tgl_invoice" 
                                           value="<?= $transaksi['tgl_invoice'] ? date('Y-m-d', strtotime($transaksi['tgl_invoice'])) : '' ?>" required
                                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all">
                                </div>

                                <div class="p-5 bg-white border border-slate-200 rounded-2xl space-y-3 shadow-xs">
                                    <label for="tanggal_transaksi" class="text-xs font-bold uppercase text-slate-500 tracking-wider flex items-center space-x-1.5">
                                        <i class="bi bi-clock-history text-amber-500"></i>
                                        <span>Tanggal Periksa</span>
                                    </label>
                                    <input type="datetime-local" name="tanggal_transaksi" id="tanggal_transaksi" 
                                           value="<?= date('Y-m-d\TH:i', strtotime($transaksi['tanggal_transaksi'])) ?>" required
                                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all">
                                </div>
                                
                                <div class="p-5 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                                    <h4 class="text-xs font-bold uppercase text-slate-400 tracking-wider flex items-center space-x-1.5">
                                        <i class="bi bi-person-bounding-box text-slate-500"></i>
                                        <span>Detail Pasien</span>
                                    </h4>
                                    <div class="space-y-2 text-sm text-slate-700">
                                        <p><strong>Nama:</strong> <?= htmlspecialchars($transaksi['nama_pasien']) ?></p>
                                        <p><strong>NIK:</strong> <?= htmlspecialchars($transaksi['nik']) ?></p>
                                        <p><strong>BPJS:</strong> <?= $transaksi['no_bpjs'] ? htmlspecialchars($transaksi['no_bpjs']) : '<span class="text-slate-400 italic">Umum</span>' ?></p>
                                        <p class="text-xs text-slate-500 leading-relaxed"><strong>Alamat:</strong> <?= htmlspecialchars($transaksi['alamat']) ?></p>
                                    </div>
                                </div>

                                <div class="p-5 bg-amber-50 border border-amber-100 text-amber-900 rounded-2xl text-center space-y-1.5">
                                    <p class="text-xs font-bold uppercase text-amber-700 tracking-wide">Nomor Invoice:</p>
                                    <span class="font-mono text-2xl font-black tracking-widest block text-slate-800"><?= htmlspecialchars($transaksi['no_invoice']) ?></span>
                                </div>

                                <div class="p-5 bg-slate-900 text-white rounded-2xl flex flex-col justify-center space-y-1">
                                    <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">Total Biaya Uji Lab</span>
                                    <h3 class="text-3xl font-black text-amber-400 tracking-tight">Rp <span id="totalDisplay"><?= number_format($transaksi['total_harga'], 0, ',', '.') ?></span></h3>
                                </div>

                                <div class="p-5 bg-white border border-slate-200 rounded-2xl space-y-3 shadow-xs">
                                    <label for="status" class="text-xs font-bold uppercase text-slate-500 tracking-wider flex items-center space-x-1.5">
                                        <i class="bi bi-circle-fill text-amber-500"></i>
                                        <span>Status Pembayaran</span>
                                    </label>
                                    <select name="status" id="status" 
                                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all">
                                        <option value="belum lunas" <?= $transaksi['status'] == 'belum lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                                        <option value="disetujui" <?= $transaksi['status'] == 'disetujui' ? 'selected' : '' ?>>Disetujui / Lunas</option>
                                    </select>
                                </div>

                                <div class="pt-4 flex items-center space-x-3 hidden lg:flex">
                                    <button type="submit" name="update_transaksi" 
                                            class="flex-1 px-5 py-3 bg-amber-500 text-white rounded-xl text-sm font-semibold hover:bg-amber-600 shadow-md hover:shadow-amber-500/10 transition-all flex items-center justify-center space-x-2">
                                        <i class="bi bi-save"></i>
                                        <span>Update Transaksi</span>
                                    </button>
                                    <a href="transaksi_list.php" class="px-5 py-3 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200 transition-colors">
                                        Batal
                                    </a>
                                </div>

                            </div>

                            <div class="lg:col-span-7 space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Pilih Tindakan / Uji Laboratorium</label>
                                    
                                    <label class="flex items-center space-x-2 text-xs font-semibold text-amber-600 cursor-pointer select-none">
                                        <input type="checkbox" id="checkAll" class="form-checkbox h-3.5 w-3.5 text-amber-500 border-slate-300 rounded focus:ring-amber-500/20">
                                        <span>Pilih Semua</span>
                                    </label>
                                </div>
                                
                                <div class="max-h-[420px] overflow-y-auto border border-slate-200 rounded-2xl divide-y divide-slate-100 bg-white p-2 shadow-xs">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-1">
                                        <?php foreach($pemeriksaan_list as $pemeriksaan): ?>
                                        <?php $checked = in_array($pemeriksaan['id'], $selected_pemeriksaan) ? 'checked' : ''; ?>
                                        <label for="check_<?= $pemeriksaan['id'] ?>" class="flex items-center justify-between p-3 border border-slate-100 rounded-xl hover:bg-slate-50 hover:border-slate-200 cursor-pointer transition-all <?= $checked ? 'bg-emerald-50 border-emerald-200' : '' ?>">
                                            <div class="flex items-center space-x-3 min-w-0">
                                                <input type="checkbox" name="pemeriksaan[]" value="<?= $pemeriksaan['id'] ?>" id="check_<?= $pemeriksaan['id'] ?>" 
                                                       class="form-checkbox h-4 w-4 text-amber-500 border-slate-300 rounded focus:ring-amber-500/20 hitung shrink-0" 
                                                       data-harga="<?= $pemeriksaan['harga'] ?>"
                                                       <?= $checked ?>>
                                                <span class="text-sm font-medium text-slate-700 truncate"><?= htmlspecialchars($pemeriksaan['nama_pemeriksaan']) ?></span>
                                            </div>
                                            <span class="text-xs font-bold text-slate-900 shrink-0 ml-2">Rp <?= number_format($pemeriksaan['harga'], 0, ',', '.') ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="pt-6 flex items-center space-x-3 border-t border-slate-100 lg:hidden">
                            <button type="submit" name="update_transaksi" 
                                    class="flex-1 px-5 py-3 bg-amber-500 text-white rounded-xl text-sm font-semibold hover:bg-amber-600 transition-all flex items-center justify-center space-x-2">
                                <i class="bi bi-save"></i>
                                <span>Update Transaksi</span>
                            </button>
                            <a href="transaksi_list.php" class="px-5 py-3 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200 transition-colors">
                                Batal
                            </a>
                        </div>

                    </form>

                </div>
            </div>
        </main>
    </div>

    <!-- Modal Pilih Pasien -->
    <div id="pasienModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" onclick="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
                <div class="bg-slate-900 px-6 py-4 text-white flex items-center justify-between">
                    <h3 class="text-base font-bold flex items-center space-x-2">
                        <i class="bi bi-people text-amber-400"></i>
                        <span>Cari & Pilih Pasien</span>
                    </h3>
                    <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-white transition-colors text-lg">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                
                <div class="p-6 space-y-4">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="keywordPasien" onkeyup="cariPasienLive()" placeholder="Ketik Nama atau NIK Pasien..." 
                               class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10">
                    </div>

                    <div class="max-h-72 overflow-y-auto border border-slate-100 rounded-xl divide-y divide-slate-100">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase sticky top-0">
                                <tr>
                                    <th class="p-3">Nama Pasien</th>
                                    <th class="p-3">NIK</th>
                                    <th class="p-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPasien">
                                <?php foreach($pasien_list as $p): ?>
                                <tr class="row-pasien hover:bg-slate-50 transition-colors <?= $p['id'] == $transaksi['pasien_id'] ? 'bg-emerald-50' : '' ?>">
                                    <td class="p-3 font-semibold text-slate-800 nama-target"><?= htmlspecialchars($p['nama']) ?></td>
                                    <td class="p-3 font-mono text-xs text-slate-500 nik-target"><?= htmlspecialchars($p['nik']) ?></td>
                                    <td class="p-3 text-center">
                                        <form method="POST">
                                            <input type="hidden" name="pasien_id" value="<?= $p['id'] ?>">
                                            <button type="submit" name="pilih_pasien" class="px-3 py-1 bg-amber-500 text-white text-xs font-bold rounded-lg hover:bg-amber-600 transition-colors">
                                                Pilih
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('pasienModal').classList.remove('hidden');
            document.getElementById('keywordPasien').focus();
        }
        function closeModal() {
            document.getElementById('pasienModal').classList.add('hidden');
        }

        function cariPasienLive() {
            let input = document.getElementById("keywordPasien").value.toLowerCase();
            let rows = document.getElementsByClassName("row-pasien");

            for (let i = 0; i < rows.length; i++) {
                let nama = rows[i].querySelector(".nama-target").innerText.toLowerCase();
                let nik = rows[i].querySelector(".nik-target").innerText.toLowerCase();
                
                if (nama.includes(input) || nik.includes(input)) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }

        function hitungTotal() {
            let total = 0;
            document.querySelectorAll('.hitung:checked').forEach(cb => {
                total += parseInt(cb.dataset.harga) || 0;
            });
            document.getElementById('totalDisplay').innerText = total.toLocaleString('id-ID');
        }
        
        document.querySelectorAll('.hitung').forEach(cb => {
            cb.addEventListener('change', hitungTotal);
        });

        const checkAll = document.getElementById('checkAll');
        if (checkAll) {
            checkAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.hitung');
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
                hitungTotal();
            });
        }

        // Hitung total saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            hitungTotal();
        });
    </script>
</body>
</html>
<?php 
ob_end_flush(); 
?>