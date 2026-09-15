<?php
ob_start();
date_default_timezone_set('Asia/Jakarta');
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

function buatNomorInvoice() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT MAX(id) AS last_id FROM transaksi");
        $row = $stmt->fetch();
        
        $next_urut = 1;
        if ($row && $row['last_id'] !== null) {
            $next_urut = (int)$row['last_id'] + 1;
        }
        $nomor_urut = str_pad($next_urut, 3, "0", STR_PAD_LEFT);
        return $nomor_urut . "/UD.06.01";
        
    } catch (PDOException $e) {
        return "001/UD.06.01";
    }
}

// Hanya ambil jenis pemeriksaan (Data pasien dipanggil via AJAX)
$pemeriksaan_list = $pdo->query("SELECT * FROM jenis_pemeriksaan ORDER BY nama_pemeriksaan ASC")->fetchAll();

$selected_pasien = null;
$preview_invoice = buatNomorInvoice();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (!empty($_POST['pasien_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM pasien WHERE id = ?");
        $stmt->execute([$_POST['pasien_id']]);
        $selected_pasien = $stmt->fetch();
    }
    
    if (isset($_POST['simpan_transaksi'])) {
        try {
            $pasien_id = $_POST['pasien_id'];
            $tgl_invoice = $_POST['tgl_invoice'];
            
            if (empty($tgl_invoice)) {
                throw new Exception("Silakan tentukan tanggal invoice terlebih dahulu.");
            }

            if (!isset($_POST['pemeriksaan']) || empty($_POST['pemeriksaan'])) {
                throw new Exception("Silakan pilih minimal satu jenis pemeriksaan.");
            }
            
            $pemeriksaan_ids = $_POST['pemeriksaan'];
            $total = 0;
            $detail_tarif = [];

            $pdo->beginTransaction();
            
            $no_invoice = buatNomorInvoice();
            
            $placeholders = implode(',', array_fill(0, count($pemeriksaan_ids), '?'));
            $stmt = $pdo->prepare("SELECT id, harga FROM jenis_pemeriksaan WHERE id IN ($placeholders)");
            $stmt->execute($pemeriksaan_ids);
            $harga_items = $stmt->fetchAll();
            
            foreach ($harga_items as $item) {
                $detail_tarif[$item['id']] = $item['harga'];
                $total += $item['harga'];
            }
            
            // Sesuaikan kolom status dengan enum DB Anda (misal: 'pending')
            $stmt = $pdo->prepare("INSERT INTO transaksi (no_invoice, tgl_invoice, pasien_id, tanggal_transaksi, total_harga, status, created_by) VALUES (?, ?, ?, NOW(), ?, 'pending', ?)");
            $stmt->execute([$no_invoice, $tgl_invoice, $pasien_id, $total, $_SESSION['user_id']]);
            $transaksi_id = $pdo->lastInsertId();
            
            $stmt_detail = $pdo->prepare("INSERT INTO detail_transaksi (transaksi_id, pemeriksaan_id, harga) VALUES (?, ?, ?)");
            foreach ($pemeriksaan_ids as $pid) {
                $harga_layanan = isset($detail_tarif[$pid]) ? $detail_tarif[$pid] : 0;
                $stmt_detail->execute([$transaksi_id, $pid, $harga_layanan]);
            }
            
            $pdo->commit();
            
            redirect("invoice.php?id=$transaksi_id");
            exit;
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Baru - Klinik LAB</title>
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
                <span class="text-slate-600 font-medium">Transaksi Baru</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mx-auto">
                
                <div class="bg-gradient-to-r from-slate-900 to-slate-800 p-5 text-white flex items-center space-x-3">
                    <div class="p-2 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-xl">
                        <i class="bi bi-wallet2 text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold tracking-wide">Panel Transaksi Laboratorium</h2>
                        <p class="text-xs text-slate-400">Pilih pasien dan tentukan paket pemeriksaan dalam satu layar terintegrasi.</p>
                    </div>
                </div>

                <div class="p-6 md:p-8 space-y-6">

                    <?php if(isset($error)): ?>
                        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center space-x-3 text-sm">
                            <i class="bi bi-exclamation-triangle-fill text-rose-500 text-lg"></i>
                            <span class="font-medium"><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pasien Rekam Medis</label>
                        <div class="flex space-x-3">
                            <div class="flex-1 px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-200 text-sm text-slate-700 flex items-center justify-between">
                                <span id="labelPasienTerpilih" class="font-medium">
                                    <?= $selected_pasien ? htmlspecialchars($selected_pasien['nama']) . ' — [' . htmlspecialchars($selected_pasien['nik']) . ']' : '-- Silakan klik tombol di samping untuk mencari pasien terdaftar --' ?>
                                </span>
                                <?php if($selected_pasien): ?>
                                    <span class="text-xs px-2 py-0.5 bg-emerald-100 text-emerald-800 font-bold rounded-md">Aktif</span>
                                <?php endif; ?>
                            </div>
                            <button type="button" onclick="openModal()" 
                                    class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-800 transition-colors flex items-center space-x-2 shrink-0">
                                <i class="bi bi-search"></i>
                                <span>Cari Pasien</span>
                            </button>
                        </div>
                    </div>

                    <?php if($selected_pasien): ?>
                    
                    <form method="POST" class="border-t border-slate-100 pt-6">
                        <input type="hidden" name="pasien_id" value="<?= $selected_pasien['id'] ?>">

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                            
                            <div class="lg:col-span-5 space-y-5">
                                
                                <div class="p-5 bg-white border border-slate-200 rounded-2xl space-y-3 shadow-xs">
                                    <label for="tgl_invoice" class="text-xs font-bold uppercase text-slate-500 tracking-wider flex items-center space-x-1.5">
                                        <i class="bi bi-calendar-week text-emerald-500"></i>
                                        <span>Tanggal Invoice</span>
                                    </label>
                                    <input type="date" name="tgl_invoice" id="tgl_invoice" 
                                           value="<?= date('Y-m-d') ?>" required
                                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all">
                                </div>
                                
                                <div class="p-5 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                                    <h4 class="text-xs font-bold uppercase text-slate-400 tracking-wider flex items-center space-x-1.5">
                                        <i class="bi bi-person-bounding-box text-slate-500"></i>
                                        <span>Detail Ringkas Pasien</span>
                                    </h4>
                                    <div class="space-y-2 text-sm text-slate-700">
                                        <p><strong>Nama:</strong> <?= htmlspecialchars($selected_pasien['nama']) ?></p>
                                        <p><strong>NIK:</strong> <?= htmlspecialchars($selected_pasien['nik']) ?></p>
                                        <p><strong>BPJS:</strong> <?= $selected_pasien['no_bpjs'] ? htmlspecialchars($selected_pasien['no_bpjs']) : '<span class="text-slate-400 italic">Umum</span>' ?></p>
                                        <p class="text-xs text-slate-500 leading-relaxed"><strong>Alamat:</strong> <?= htmlspecialchars($selected_pasien['alamat']) ?></p>
                                    </div>
                                </div>

                                <div class="p-5 bg-emerald-50 border border-emerald-100 text-emerald-900 rounded-2xl text-center space-y-1.5">
                                    <p class="text-xs font-bold uppercase text-emerald-700 tracking-wide">Nomor Invoice Registrasi:</p>
                                    <span class="font-mono text-2xl font-black tracking-widest block text-slate-800"><?= htmlspecialchars($preview_invoice) ?></span>
                                </div>

                                <div class="p-5 bg-slate-900 text-white rounded-2xl flex flex-col justify-center space-y-1">
                                    <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">Total Est. Biaya Uji Lab</span>
                                    <h3 class="text-3xl font-black text-emerald-400 tracking-tight">Rp <span id="totalDisplay">0</span></h3>
                                </div>

                                <div class="pt-4 flex items-center space-x-3 hidden lg:flex">
                                    <button type="submit" name="simpan_transaksi" 
                                            class="flex-1 px-5 py-3 bg-emerald-500 text-white rounded-xl text-sm font-semibold hover:bg-emerald-600 shadow-md hover:shadow-emerald-500/10 transition-all flex items-center justify-center space-x-2">
                                        <i class="bi bi-file-earmark-check"></i>
                                        <span>Proses & Cetak</span>
                                    </button>
                                    <a href="transaksi_form.php" class="px-5 py-3 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200 transition-colors">
                                        Reset
                                    </a>
                                </div>

                            </div>

                            <div class="lg:col-span-7 space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Pilih Tindakan / Uji Laboratorium</label>
                                    
                                    <label class="flex items-center space-x-2 text-xs font-semibold text-emerald-600 cursor-pointer select-none">
                                        <input type="checkbox" id="checkAll" class="form-checkbox h-3.5 w-3.5 text-emerald-500 border-slate-300 rounded focus:ring-emerald-500/20">
                                        <span>Pilih Semua</span>
                                    </label>
                                </div>
                                
                                <div class="max-h-[420px] overflow-y-auto border border-slate-200 rounded-2xl divide-y divide-slate-100 bg-white p-2 shadow-xs">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-1">
                                        <?php foreach($pemeriksaan_list as $pemeriksaan): ?>
                                        <label for="check_<?= $pemeriksaan['id'] ?>" class="flex items-center justify-between p-3 border border-slate-100 rounded-xl hover:bg-slate-50 hover:border-slate-200 cursor-pointer transition-all">
                                            <div class="flex items-center space-x-3 min-w-0">
                                                <input type="checkbox" name="pemeriksaan[]" value="<?= $pemeriksaan['id'] ?>" id="check_<?= $pemeriksaan['id'] ?>" 
                                                       class="form-checkbox h-4 w-4 text-emerald-500 border-slate-300 rounded focus:ring-emerald-500/20 hitung shrink-0" 
                                                       data-harga="<?= $pemeriksaan['harga'] ?>">
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
                            <button type="submit" name="simpan_transaksi" 
                                    class="flex-1 px-5 py-3 bg-emerald-500 text-white rounded-xl text-sm font-semibold hover:bg-emerald-600 transition-all flex items-center justify-center space-x-2">
                                <i class="bi bi-file-earmark-check"></i>
                                <span>Proses & Cetak</span>
                            </button>
                            <a href="transaksi.php" class="px-5 py-3 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200 transition-colors">
                                Reset
                            </a>
                        </div>

                    </form>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>

    <!-- Modal Pasien Ringan dengan AJAX -->
    <div id="pasienModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" onclick="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
                <div class="bg-slate-900 px-6 py-4 text-white flex items-center justify-between">
                    <h3 class="text-base font-bold flex items-center space-x-2">
                        <i class="bi bi-people text-emerald-400"></i>
                        <span>Cari & Pilih Pasien Klinik</span>
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
                        <input type="text" id="keywordPasien" oninput="cariPasienAJAX()" placeholder="Ketik Nama atau NIK Pasien..." 
                               class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">
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
                                <tr>
                                    <td colspan="3" class="p-4 text-center text-slate-400 text-xs">Mengambil data pasien...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let debounceTimer;

        function openModal() {
            document.getElementById('pasienModal').classList.remove('hidden');
            document.getElementById('keywordPasien').focus();
            fetchPasien(''); // Load data default saat modal dibuka
        }

        function closeModal() {
            document.getElementById('pasienModal').classList.add('hidden');
        }

        function cariPasienAJAX() {
            clearTimeout(debounceTimer);
            const query = document.getElementById("keywordPasien").value;
            // Debounce 300ms agar server tidak terbebani setiap ketikan tombol
            debounceTimer = setTimeout(() => {
                fetchPasien(query);
            }, 300);
        }

        function fetchPasien(query) {
            const tbody = document.getElementById('tbodyPasien');
            tbody.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-slate-400 text-xs">Mencari...</td></tr>';

            fetch(`api_search_pasien.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-slate-400 text-xs">Pasien tidak ditemukan.</td></tr>';
                        return;
                    }

                    let html = '';
                    data.forEach(p => {
                        html += `
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-3 font-semibold text-slate-800">${escapeHtml(p.nama)}</td>
                            <td class="p-3 font-mono text-xs text-slate-500">${escapeHtml(p.nik)}</td>
                            <td class="p-3 text-center">
                                <form method="POST">
                                    <input type="hidden" name="pasien_id" value="${p.id}">
                                    <button type="submit" name="pilih_pasien" class="px-3 py-1 bg-emerald-500 text-white text-xs font-bold rounded-lg hover:bg-emerald-600 transition-colors">
                                        Pilih
                                    </button>
                                </form>
                            </td>
                        </tr>`;
                    });
                    tbody.innerHTML = html;
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-rose-500 text-xs">Gagal mengambil data.</td></tr>';
                });
        }

        function escapeHtml(text) {
            return String(text ?? '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#032;");
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
    </script>
</body>
</html>
<?php 
ob_end_flush(); 
?>