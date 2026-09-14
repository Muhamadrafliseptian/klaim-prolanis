<?php
require_once 'config.php';
// require_once 'sidebar.php'; // Pindahan ke dalam layout body HTML bawah

if (!isLoggedIn()) {
    redirect('login.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO pasien (nama, tanggal_lahir, nik, jenis_kelamin, no_bpjs, alamat) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['nama'],
            $_POST['tanggal_lahir'],
            $_POST['nik'],
            $_POST['jenis_kelamin'],
            $_POST['no_bpjs'],
            $_POST['alamat']
        ]);
        $success = 'Data pasien berhasil disimpan!';
    } catch(PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pasien - Klinik LAB</title>
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
                <span class="text-slate-600 font-medium">Input Pasien</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                
                <div class="bg-gradient-to-r from-slate-900 to-slate-800 p-5 text-white flex items-center space-x-3">
                    <div class="p-2 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-xl">
                        <i class="bi bi-person-plus text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold tracking-wide">Form Input Data Pasien</h2>
                        <p class="text-xs text-slate-400">Silakan lengkapi berkas rekam medis pasien baru di bawah ini.</p>
                    </div>
                </div>

                <div class="p-6 md:p-8">
                    
                    <?php if($success): ?>
                        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center space-x-3 text-sm">
                            <i class="bi bi-check-circle-fill text-emerald-500 text-lg"></i>
                            <span class="font-medium"><?= $success ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center space-x-3 text-sm">
                            <i class="bi bi-exclamation-triangle-fill text-rose-500 text-lg"></i>
                            <span class="font-medium"><?= $error ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div class="flex flex-col space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Lengkap</label>
                                <input type="text" name="nama" placeholder="Contoh: Budi Santoso" 
                                       class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all text-slate-800" required>
                            </div>

                            <div class="flex flex-col space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" 
                                       class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all text-slate-800" required>
                            </div>

                            <div class="flex flex-col space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">NIK (Nomor Induk Kependudukan)</label>
                                <input type="text" name="nik" placeholder="16 Digit Nomor KTP" maxlength="16"
                                       class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all text-slate-800" required>
                            </div>

                            <div class="flex flex-col space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jenis Kelamin</label>
                                <select name="jenis_kelamin" 
                                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all bg-white text-slate-800" required>
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>

                            <div class="flex flex-col space-y-1.5 md:col-span-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">No. BPJS <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                <input type="text" name="no_bpjs" placeholder="Kosongkan jika pasien umum / non-BPJS"
                                       class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all text-slate-800">
                            </div>

                            <div class="flex flex-col space-y-1.5 md:col-span-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Alamat Rumah Lengkap</label>
                                <textarea name="alamat" rows="3" placeholder="Nama jalan, RT/RW, Kecamatan, Kota/Kabupaten..."
                                          class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all text-slate-800" required></textarea>
                            </div>

                        </div>

                        <div class="pt-4 flex items-center space-x-3 border-t border-slate-100">
                            <button type="submit" 
                                    class="px-5 py-2.5 bg-emerald-500 text-white rounded-xl text-sm font-semibold hover:bg-emerald-600 focus:ring-4 focus:ring-emerald-500/20 shadow-xs transition-all flex items-center space-x-2">
                                <i class="bi bi-save"></i>
                                <span>Simpan Pasien</span>
                            </button>
                            <a href="dashboard.php" 
                               class="px-5 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200 transition-colors">
                                Kembali
                            </a>
                        </div>
                    </form>

                </div>
            </div>

        </main>
    </div>

</body>
</html>