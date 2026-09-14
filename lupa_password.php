<?php
require_once 'config.php';

// Jika sudah login, kunci akses halaman ini dan lempar ke dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['proses_reset'])) {
    $username = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    
    if (empty($username) || empty($nama_lengkap)) {
        $error = 'Semua kolom verifikasi wajib diisi!';
    } else {
        // Cocokkan kombinasi username dan nama lengkap untuk validasi kepemilikan akun
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND nama_lengkap = ?");
        $stmt->execute([$username, $nama_lengkap]);
        $user = $stmt->fetch();
        
        if ($user) {
            if ($user['id'] == 1) {
                // Proteksi Akun Utama Administrator demi keamanan sistem
                $error = 'Akun Administrator utama tidak dapat di-reset mandiri. Silakan hubungi pengembang database!';
            } else {
                // Reset password ke default: "klinik123" menggunakan enkripsi hash modern
                $password_default = 'klinik123';
                $password_hash = password_hash($password_default, PASSWORD_DEFAULT);
                
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$password_hash, $user['id']]);
                
                $success = "Verifikasi berhasil! Kata sandi akun Anda telah dikembalikan ke default: <strong class='underline'>$password_default</strong>. Silakan login kembali dan segera ganti sandi Anda di menu kelola user.";
            }
        } else {
            $error = 'Kombinasi Username dan Nama Lengkap tidak terdaftar di database kami!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemulihan Kata Sandi - Klinik LAB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-slate-900 font-sans antialiased min-h-screen flex items-center justify-center p-4 relative overflow-hidden">

    <div class="absolute top-0 right-0 w-80 h-80 bg-rose-500/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-80 h-80 bg-emerald-500/5 rounded-full blur-3xl"></div>

    <div class="w-full max-w-md bg-slate-800/60 backdrop-blur-xl border border-slate-700/60 p-6 sm:p-8 rounded-3xl shadow-2xl relative z-10 space-y-6">
        
        <div class="text-center space-y-2">
            <div class="inline-flex p-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-2xl text-2xl shadow-md">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h1 class="text-xl font-black text-white tracking-tight">Pemulihan Akun Staf</h1>
            <p class="text-xs text-slate-400 font-medium max-w-xs mx-auto">
                Masukkan kredensial identitas Anda yang terdaftar untuk mengatur ulang kata sandi yang lupa.
            </p>
        </div>

        <?php if(!empty($error)): ?>
            <div class="p-4 bg-rose-950/40 border border-rose-800/60 text-rose-200 rounded-xl flex items-start space-x-3 text-xs">
                <i class="bi bi-exclamation-octagon-fill text-rose-500 text-base shrink-0"></i>
                <div class="flex-1 leading-normal"><?= $error ?></div>
            </div>
        <?php endif; ?>

        <?php if(!empty($success)): ?>
            <div class="p-4 bg-emerald-950/50 border border-emerald-800/60 text-emerald-200 rounded-xl flex items-start space-x-3 text-xs">
                <i class="bi bi-check-circle-fill text-emerald-400 text-base shrink-0"></i>
                <div class="flex-1 leading-relaxed"><?= $success ?></div>
            </div>
        <?php endif; ?>

        <?php if(empty($success)): ?>
        <form method="POST" action="" class="space-y-4">
            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Username Anda</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                        <i class="bi bi-person"></i>
                    </span>
                    <input type="text" name="username" required placeholder="Contoh: petugas_lab"
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-900/60 border border-slate-700 rounded-xl text-sm font-medium text-white placeholder-slate-500 focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Nama Lengkap (Sesuai SK/Database)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                        <i class="bi bi-card-text"></i>
                    </span>
                    <input type="text" name="nama_lengkap" required placeholder="Contoh: Dr. Budi Utomo"
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-900/60 border border-slate-700 rounded-xl text-sm font-medium text-white placeholder-slate-500 focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all">
                </div>
                <p class="text-[10px] text-slate-500 mt-1 pl-1">
                    *Gunakan ejaan huruf kapital yang sesuai saat akun Anda pertama kali dibuat.
                </p>
            </div>

            <button type="submit" name="proses_reset"
                    class="w-full py-3 bg-rose-500 text-white rounded-xl text-sm font-bold hover:bg-rose-600 transition-all flex items-center justify-center space-x-2 shadow-lg shadow-rose-500/10">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>Reset ke Password Default</span>
            </button>
        </form>
        <?php endif; ?>

        <div class="pt-2 text-center">
            <a href="login.php" class="text-xs text-slate-400 hover:text-white transition-colors inline-flex items-center space-x-1 font-medium">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali ke Halaman Login</span>
            </a>
        </div>

    </div>

</body>
</html>