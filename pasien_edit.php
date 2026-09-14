<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Ambil ID pasien dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    redirect('pasien_list.php');
}

// Ambil data pasien yang akan diedit
try {
    $stmt = $pdo->prepare("SELECT * FROM pasien WHERE id = ?");
    $stmt->execute([$id]);
    $pasien = $stmt->fetch();
    
    if (!$pasien) {
        redirect('pasien_list.php');
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$success = '';
$error = '';

// Proses update data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_pasien'])) {
    try {
        // Validasi NIK (harus 16 digit)
        $nik = preg_replace('/[^0-9]/', '', $_POST['nik']);
        if (strlen($nik) != 16) {
            throw new Exception("NIK harus 16 digit angka.");
        }
        
        // Cek duplikat NIK (kecuali untuk data sendiri)
        $stmt = $pdo->prepare("SELECT id FROM pasien WHERE nik = ? AND id != ?");
        $stmt->execute([$nik, $id]);
        if ($stmt->fetch()) {
            throw new Exception("NIK sudah terdaftar untuk pasien lain.");
        }
        
        $stmt = $pdo->prepare("
            UPDATE pasien 
            SET nama = ?, 
                tanggal_lahir = ?, 
                nik = ?, 
                jenis_kelamin = ?, 
                no_bpjs = ?, 
                alamat = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['nama'],
            $_POST['tanggal_lahir'],
            $nik,
            $_POST['jenis_kelamin'],
            $_POST['no_bpjs'],
            $_POST['alamat'],
            $id
        ]);
        
        $success = 'Data pasien berhasil diperbarui!';
        
        // Refresh data setelah update
        $stmt = $pdo->prepare("SELECT * FROM pasien WHERE id = ?");
        $stmt->execute([$id]);
        $pasien = $stmt->fetch();
        
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pasien - Klinik LAB</title>
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
                <a href="pasien_list.php" class="hover:text-emerald-500 transition-colors">Daftar Pasien</a>
                <span>/</span>
                <span class="text-slate-600 font-medium">Edit Pasien</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                
                <div class="bg-gradient-to-r from-cyan-600 to-cyan-700 p-5 text-white flex items-center space-x-3">
                    <div class="p-2 bg-white/20 text-white border border-white/20 rounded-xl">
                        <i class="bi bi-pencil-square text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold tracking-wide">Edit Data Pasien</h2>
                        <p class="text-xs text-white/80">Perbarui informasi rekam medis pasien di bawah ini.</p>
                    </div>
                </div>

                <div class="p-6 md:p-8">
                    
                    <?php if($success): ?>
                        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center space-x-3 text-sm">
                            <i class="bi bi-check-circle-fill text-emerald-500 text-lg"></i>
                            <span class="font-medium"><?= $success ?></span>
                            <button onclick="this.parentElement.remove()" class="ml-auto text-emerald-400 hover:text-emerald-600">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center space-x-3 text-sm">
                            <i class="bi bi-exclamation-triangle-fill text-rose-500 text-lg"></i>
                            <span class="font-medium"><?= $error ?></span>
                            <button onclick="this.parentElement.remove()" class="ml-auto text-rose-400 hover:text-rose-600">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div class="flex flex-col space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Lengkap</label>
                                <input type="text" name="nama" placeholder="Contoh: Budi Santoso" 
                                       value="<?= htmlspecialchars($pasien['nama']) ?>"
                                       class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-hidden focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 transition-all text-slate-800" required>
                            </div>

                            <div class="flex flex-col space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" 
                                       value="<?= $pasien['tanggal_lahir'] ?>"
                                       class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-hidden focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 transition-all text-slate-800" required>
                            </div>

                            <div class="flex flex-col space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">NIK (Nomor Induk Kependudukan)</label>
                                <input type="text" name="nik" placeholder="16 Digit Nomor KTP" maxlength="16"
                                       value="<?= htmlspecialchars($pasien['nik']) ?>"
                                       class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-hidden focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 transition-all text-slate-800" required>
                                <p class="text-xs text-slate-400">Masukkan 16 digit angka NIK.</p>
                            </div>

                            <div class="flex flex-col space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jenis Kelamin</label>
                                <select name="jenis_kelamin" 
                                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-hidden focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 transition-all bg-white text-slate-800" required>
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="L" <?= $pasien['jenis_kelamin'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="P" <?= $pasien['jenis_kelamin'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                            </div>

                            <div class="flex flex-col space-y-1.5 md:col-span-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">No. BPJS <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                <input type="text" name="no_bpjs" placeholder="Kosongkan jika pasien umum / non-BPJS"
                                       value="<?= htmlspecialchars($pasien['no_bpjs']) ?>"
                                       class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-hidden focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 transition-all text-slate-800">
                            </div>

                            <div class="flex flex-col space-y-1.5 md:col-span-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Alamat Rumah Lengkap</label>
                                <textarea name="alamat" rows="3" placeholder="Nama jalan, RT/RW, Kecamatan, Kota/Kabupaten..."
                                          class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-hidden focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 transition-all text-slate-800" required><?= htmlspecialchars($pasien['alamat']) ?></textarea>
                            </div>

                        </div>

                        <div class="pt-4 flex items-center space-x-3 border-t border-slate-100">
                            <button type="submit" name="update_pasien"
                                    class="px-5 py-2.5 bg-cyan-600 text-white rounded-xl text-sm font-semibold hover:bg-cyan-700 focus:ring-4 focus:ring-cyan-500/20 shadow-xs transition-all flex items-center space-x-2">
                                <i class="bi bi-save"></i>
                                <span>Update Pasien</span>
                            </button>
                            <a href="pasien_list.php" 
                               class="px-5 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200 transition-colors">
                                Batal
                            </a>
                        </div>
                    </form>

                </div>
            </div>

        </main>
    </div>

    <script>
        // Auto-format NIK: hanya angka dan max 16 digit
        document.querySelector('input[name="nik"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 16);
        });

        // Auto dismiss flash message
        setTimeout(function() {
            const flash = document.querySelector('.mb-6.p-4');
            if (flash && flash.classList.contains('bg-emerald-50')) {
                flash.style.opacity = '0';
                flash.style.transition = 'opacity 0.5s ease';
                setTimeout(function() {
                    flash.remove();
                }, 500);
            }
        }, 5000);
    </script>
</body>
</html>