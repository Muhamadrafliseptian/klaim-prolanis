<?php
require_once 'config.php';

// Fitur Pembuat Gambar CAPTCHA (GD Library)
if (isset($_GET['captcha_img'])) {
    header("Content-Type: image/png");
    $code = substr(str_shuffle("23456789ABCDEFGHJKLMNPQRSTUVWXYZ"), 0, 5);
    $_SESSION['captcha_code'] = $code;

    $image = imagecreatetruecolor(120, 40);
    $bgColor = imagecolorallocate($image, 241, 245, 249); // Warna background (slate-100)
    $textColor = imagecolorallocate($image, 16, 185, 129); // Warna teks (emerald-500)
    $noiseColor = imagecolorallocate($image, 148, 163, 184); // Warna garis acak

    imagefilledrectangle($image, 0, 0, 120, 40, $bgColor);

    // Tambahkan garis noise agar tidak mudah dibaca bot
    for ($i = 0; $i < 4; $i++) {
        imageline($image, rand(0, 120), rand(0, 40), rand(0, 120), rand(0, 40), $noiseColor);
    }

    // Tulis teks captcha ke gambar
    imagestring($image, 5, 35, 12, $code, $textColor);
    imagepng($image);
    imagedestroy($image);
    exit();
}

// Jika sudah login, langsung lempar ke dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $captcha  = trim($_POST['captcha']);

    // Validasi CAPTCHA
    if (empty($_SESSION['captcha_code']) || strtoupper($captcha) !== $_SESSION['captcha_code']) {
        $error = 'Kode CAPTCHA yang Anda masukkan salah!';
    } elseif (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

            unset($_SESSION['captcha_code']);
            redirect('dashboard.php');
        } else {
            if ($user && $password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

                unset($_SESSION['captcha_code']);
                redirect('dashboard.php');
            } else {
                $error = 'Username atau password yang Anda masukkan salah!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Masuk Sistem - Klinik LAB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-slate-900 font-sans antialiased min-h-screen flex flex-col md:flex-row">

    <div class="hidden md:flex md:w-1/2 lg:w-3/5 bg-cover bg-center relative items-center p-12" 
         style="background-image: url('https://images.unsplash.com/photo-1579165466541-71e226d7a56e?auto=format&fit=crop&q=80&w=1200');">
        <div class="absolute inset-0 bg-gradient-to-tr from-slate-950 via-slate-900/80 to-emerald-950/40 backdrop-blur-xs"></div>
        
        <div class="relative z-10 max-w-xl space-y-6 text-white">
            <div class="inline-flex p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-emerald-400 text-3xl shadow-lg">
                <i class="bi bi-activity"></i>
            </div>
            <div class="space-y-2">
                <h1 class="text-3xl lg:text-5xl font-black tracking-tight leading-none">
                    Sistem Manajemen <br><span class="text-emerald-400">Laboratorium Klinik</span>
                </h1>
                <p class="text-sm lg:text-base text-slate-300 font-medium leading-relaxed">
                    Integrasi pencatatan data rekam medis pasien, kalkulasi tarif uji lab instan, dan pencetakan invoice digital dalam satu platform penunjang medis.
                </p>
            </div>
            <div class="pt-4 flex items-center space-x-6 border-t border-slate-800 text-xs text-slate-400 font-mono">
                <div>VERSION <span class="text-slate-200 font-bold">2.4.0</span></div>
                <div>SECURE SSL <span class="text-emerald-400 font-bold">ACTIVE</span></div>
            </div>
        </div>
    </div>

    <div class="flex-1 md:w-1/2 lg:w-2/5 bg-slate-50 flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-16 relative">
        
        <div class="mx-auto w-full max-w-md space-y-8">
            <div class="space-y-2">
                <div class="flex items-center space-x-2 text-slate-900 md:hidden">
                    <span class="p-2 bg-emerald-500 text-white rounded-lg text-lg"><i class="bi bi-flask"></i></span>
                    <span class="text-xl font-black tracking-wider">Klinik LAB</span>
                </div>
                <h2 class="text-2xl font-black text-slate-800 tracking-tight">Selamat Datang Kembali</h2>
                <p class="text-xs text-slate-500 font-medium">Gunakan hak akses resmi Anda untuk masuk ke dashboard penanganan.</p>
            </div>

            <?php if(!empty($error)): ?>
                <div id="errorAlert" class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-start space-x-3 text-xs transition-all duration-300">
                    <i class="bi bi-exclamation-triangle-fill text-rose-500 text-base shrink-0"></i>
                    <div class="flex-1 font-medium leading-normal"><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-5">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Nama Pengguna (Username)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="bi bi-person-circle"></i>
                        </span>
                        <input type="text" name="username" required autofocus placeholder="Masukkan username"
                               class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all placeholder-slate-400">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Kata Sandi (Password)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="bi bi-shield-lock-fill"></i>
                        </span>
                        <input type="password" name="password" id="password" required placeholder="••••••••"
                               class="w-full pl-10 pr-12 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all placeholder-slate-400">
                        <button type="button" onclick="togglePassword()" 
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-emerald-500 transition-colors text-sm">
                            <i id="toggleIcon" class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Kode Keamanan (CAPTCHA)</label>
                    <div class="flex items-center space-x-3 mb-2">
                        <img src="?captcha_img=1" id="captchaImg" alt="CAPTCHA" class="h-10 rounded-lg border border-slate-200 shadow-xs">
                        <button type="button" onclick="refreshCaptcha()" class="p-2 bg-slate-200 text-slate-600 rounded-lg hover:bg-slate-300 transition-colors text-xs font-semibold flex items-center space-x-1">
                            <i class="bi bi-arrow-clockwise"></i>
                            <span>Acak Kode</span>
                        </button>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="bi bi-check-square-fill"></i>
                        </span>
                        <input type="text" name="captcha" required autocomplete="off" placeholder="Masukkan 5 karakter gambar di atas"
                               class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all placeholder-slate-400 uppercase">
                    </div>
                </div>

                <button type="submit" 
                        class="w-full py-3 bg-emerald-500 text-white rounded-xl text-sm font-bold hover:bg-emerald-600 shadow-md hover:shadow-emerald-500/10 transition-all flex items-center justify-center space-x-2">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Autentikasi Masuk</span>
                </button>
            </form>

            <p class="text-[11px] text-center text-slate-400 font-medium">
                &copy; 2026 Sistem Informasi Klinik LAB. Hak Cipta Dilindungi Undang-Undang.
            </p>
        </div>

    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        function refreshCaptcha() {
            document.getElementById('captchaImg').src = '?captcha_img=' + Math.random();
        }

        setTimeout(function() {
            const alertBox = document.getElementById('errorAlert');
            if (alertBox) {
                alertBox.classList.add('opacity-0', 'scale-95');
                setTimeout(() => alertBox.remove(), 300);
            }
        }, 4000);
    </script>
</body>
</html>