<?php
require_once 'config.php';

// Proteksi halaman: Pastikan user sudah login
if (!isLoggedIn()) {
    redirect('login.php');
}

$error = '';
$success = '';

// Proses Ubah Password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ubah_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    $user_id = $_SESSION['user_id'];

    // Ambil data password saat ini di database
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Validasi input
    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
        $error = 'Semua kolom wajib diisi!';
    } elseif ($password_baru !== $konfirmasi_password) {
        $error = 'Konfirmasi password baru tidak cocok!';
    } elseif (strlen($password_baru) < 6) {
        $error = 'Password baru minimal harus 6 karakter!';
    } else {
        // Cek apakah password lama benar (mendukung bcrypt hash dan plain-text lama)
        $password_valid = false;
        if (password_verify($password_lama, $user['password'])) {
            $password_valid = true;
        } elseif ($password_lama === $user['password']) {
            $password_valid = true; // Fallback untuk akun lama plain-text
        }

        if ($password_valid) {
            // Hash password baru sebelum disimpan
            $password_hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);
            
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$password_hash_baru, $user_id]);
            
            $success = 'Password Anda berhasil diperbarui! Silakan gunakan password baru pada login berikutnya.';
        } else {
            $error = 'Password lama yang Anda masukkan salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun - Klinik LAB</title>
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
                <span class="text-slate-600 font-medium">Pengaturan Keamanan</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden mt-4">
                
                <div class="bg-gradient-to-r from-slate-900 to-slate-800 p-5 text-white flex items-center space-x-4">
                    <div class="p-2.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-xl">
                        <i class="bi bi-shield-lock text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold tracking-wide uppercase">Ubah Kata Sandi</h3>
                        <p class="text-xs text-slate-400">Perbarui kredensial masuk akun <?php echo ucfirst($_SESSION['role']); ?> Anda.</p>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 flex items-center space-x-3 text-sm">
                        <div class="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-200 text-emerald-600 flex items-center justify-center font-bold text-base">
                            <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?>
                        </div>
                        <div>
                            <div class="font-bold text-slate-800"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></div>
                            <div class="text-xs text-slate-400 font-mono">ID Pengguna: @<?php echo htmlspecialchars($_SESSION['username']); ?></div>
                        </div>
                    </div>

                    <?php if(!empty($error)): ?>
                        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-start space-x-3 text-xs">
                            <i class="bi bi-exclamation-triangle-fill text-rose-500 text-base shrink-0"></i>
                            <div class="flex-1 font-medium leading-normal"><?= $error ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($success)): ?>
                        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-start space-x-3 text-xs">
                            <i class="bi bi-check-circle-fill text-emerald-500 text-base shrink-0"></i>
                            <div class="flex-1 font-medium leading-normal"><?= $success ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Kata Sandi Saat Ini</label>
                            <input type="password" name="password_lama" required placeholder="Masukkan sandi sekarang"
                                   class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all">
                        </div>

                        <div class="border-t border-slate-100 my-2 pt-2"></div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Kata Sandi Baru</label>
                            <input type="password" name="password_baru" required placeholder="Minimal 6 karakter"
                                   class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Ulangi Kata Sandi Baru</label>
                            <input type="password" name="konfirmasi_password" required placeholder="Konfirmasi sandi baru"
                                   class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all">
                        </div>

                        <div class="pt-4 flex items-center space-x-3">
                            <button type="submit" name="ubah_password"
                                    class="flex-1 py-2.5 bg-emerald-500 text-white rounded-xl text-sm font-semibold hover:bg-emerald-600 transition-all shadow-xs flex items-center justify-center space-x-2">
                                <i class="bi bi-shield-check"></i>
                                <span>Simpan Perubahan</span>
                            </button>
                            <a href="dashboard.php" 
                               class="px-4 py-2.5 bg-slate-100 text-slate-600 text-sm font-semibold rounded-xl hover:bg-slate-200 transition-colors text-center">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>

</body>
</html>