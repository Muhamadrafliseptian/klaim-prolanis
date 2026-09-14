<?php
require_once 'config.php';

// Proteksi halaman: Hanya Administrator yang boleh masuk
if (!isLoggedIn() || !isRole('admin')) {
    redirect('login.php');
}

$error = '';
$success = '';

// Variabel untuk mode EDIT
$edit_mode = false;
$edit_id = '';
$edit_username = '';
$edit_nama_lengkap = '';
$edit_role = '';

// MODE EDIT: Mengambil data pengguna yang akan diubah ke form
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt_edit = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_edit->execute([$edit_id]);
    $user_to_edit = $stmt_edit->fetch();

    if ($user_to_edit) {
        $edit_mode = true;
        $edit_username = $user_to_edit['username'];
        $edit_nama_lengkap = $user_to_edit['nama_lengkap'];
        $edit_role = $user_to_edit['role'];
    }
}

// PROSES TAMBAH & EDIT USER
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $nama_lengkap = trim($_POST['nama_lengkap']);

    if (empty($username) || empty($nama_lengkap)) {
        $error = 'Nama lengkap dan username tidak boleh kosong!';
    } else {
        if (isset($_POST['tambah_user'])) {
            // Cek duplicate username
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetch()) {
                $error = 'Username sudah digunakan oleh staf lain!';
            } else {
                // Proses Simpan Baru
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, nama_lengkap) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $password, $role, $nama_lengkap]);
                redirect('user_manage.php?success=Data staf baru berhasil didaftarkan.');
            }
        } 
        
        elseif (isset($_POST['update_user'])) {
            $id_user = $_POST['id_user'];
            
            // Cek duplicate username milik orang lain
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check->execute([$username, $id_user]);
            if ($check->fetch()) {
                $error = 'Username tersebut sudah dipakai staf lain!';
            } else {
                // Jika password diisi, enkripsi dan ikut perbarui
                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role = ?, nama_lengkap = ? WHERE id = ?");
                    $stmt->execute([$username, $password, $role, $nama_lengkap, $id_user]);
                } else {
                    // Jika password kosong, pertahankan password lama
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ?, nama_lengkap = ? WHERE id = ?");
                    $stmt->execute([$username, $role, $nama_lengkap, $id_user]);
                }
                redirect('user_manage.php?success=Informasi akun staf berhasil diperbarui.');
            }
        }
    }
}

// PROSES HAPUS USER
if (isset($_GET['delete'])) {
    // Keamanan Berlapis: Cegah penghapusan ID 1 (Admin Utama)
    if ($_GET['delete'] != 1) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        redirect('user_manage.php?success=Akun berhasil dihapus dari database.');
    } else {
        $error = 'Akun Administrator utama sistem tidak dapat dihapus!';
    }
}

// Tangkap alert sukses via URL parameter redirect
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}

// Ambil seluruh data pengguna terbaru
$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Klinik LAB</title>
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
                <span class="text-slate-600 font-medium">Manajemen Pengguna</span>
            </div>

            <?php if(!empty($error)): ?>
                <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-start space-x-3 text-xs max-w-7xl mx-auto">
                    <i class="bi bi-exclamation-triangle-fill text-rose-500 text-base shrink-0"></i>
                    <div class="flex-1 font-medium leading-normal"><?= $error ?></div>
                </div>
            <?php endif; ?>

            <?php if(!empty($success)): ?>
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-start space-x-3 text-xs max-w-7xl mx-auto">
                    <i class="bi bi-check-circle-fill text-emerald-500 text-base shrink-0"></i>
                    <div class="flex-1 font-medium leading-normal"><?= $success ?></div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 max-w-7xl mx-auto items-start">
                
                <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="bg-gradient-to-r <?php echo $edit_mode ? 'from-amber-600 to-amber-500' : 'from-slate-900 to-slate-800'; ?> p-4 text-white flex items-center space-x-3">
                        <div class="p-2 <?php echo $edit_mode ? 'bg-amber-700/20 text-amber-100 border-amber-400/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'; ?> border rounded-xl">
                            <i class="bi <?php echo $edit_mode ? 'bi-pencil-square' : 'bi-person-plus'; ?> text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold tracking-wide"><?= $edit_mode ? 'Ubah Akun Staf' : 'Tambah User Baru'; ?></h3>
                            <p class="text-xs <?php echo $edit_mode ? 'text-amber-100/80' : 'text-slate-400'; ?>"><?= $edit_mode ? 'Sesuaikan otorisasi kredensial.' : 'Daftarkan akun staf klinik baru.'; ?></p>
                        </div>
                    </div>
                    
                    <form method="POST" action="user_manage.php" class="p-5 space-y-4">
                        <?php if($edit_mode): ?>
                            <input type="hidden" name="id_user" value="<?= $edit_id ?>">
                        <?php endif; ?>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" required value="<?= htmlspecialchars($edit_nama_lengkap) ?>" placeholder="Contoh: Dr. Budi Utomo"
                                   class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Username</label>
                            <input type="text" name="username" required value="<?= htmlspecialchars($edit_username) ?>" placeholder="username_staf"
                                   class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm font-mono focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">
                                Password <?= $edit_mode ? '<span class="text-amber-600 font-normal lowercase">(Kosongkan jika tidak diganti)</span>' : ''; ?>
                            </label>
                            <input type="password" name="password" <?= $edit_mode ? '' : 'required'; ?> placeholder="••••••••"
                                   class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Hak Akses (Role)</label>
                            <select name="role" required 
                                    class="w-full px-3 py-2 border border-slate-200 bg-white rounded-xl text-sm focus:outline-hidden focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all">
                                <option value="admin" <?= $edit_role == 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="petugas" <?= $edit_role == 'petugas' ? 'selected' : '' ?>>Petugas Lab</option>
                                <option value="bendahara" <?= $edit_role == 'bendahara' ? 'selected' : '' ?>>Bendahara / Kasir</option>
                            </select>
                        </div>

                        <div class="pt-2 space-y-2">
                            <?php if($edit_mode): ?>
                                <button type="submit" name="update_user" 
                                        class="w-full py-2.5 bg-amber-500 text-white rounded-xl text-sm font-semibold hover:bg-amber-600 shadow-xs transition-all flex items-center justify-center space-x-2">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Simpan Perubahan</span>
                                </button>
                                <a href="user_manage.php" 
                                   class="w-full py-2.5 bg-slate-100 text-slate-600 text-sm font-semibold rounded-xl hover:bg-slate-200 transition-colors flex items-center justify-center">
                                    Batal / Tambah Baru
                                </a>
                            <?php else: ?>
                                <button type="submit" name="tambah_user" 
                                        class="w-full py-2.5 bg-emerald-500 text-white rounded-xl text-sm font-semibold hover:bg-emerald-600 shadow-xs transition-all flex items-center justify-center space-x-2">
                                    <i class="bi bi-check-circle"></i>
                                    <span>Simpan Akun Baru</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="bg-gradient-to-r from-slate-900 to-slate-800 p-4 text-white flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 rounded-xl">
                                <i class="bi bi-shield-lock text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold tracking-wide">Daftar Pengguna Sistem</h3>
                                <p class="text-xs text-slate-400">Total kredensial terdaftar yang memiliki otoritas akses.</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 overflow-x-auto">
                        <table class="w-full text-sm text-left text-slate-600 border-collapse">
                            <thead class="text-xs uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 text-center rounded-l-lg">ID</th>
                                    <th class="px-4 py-3">Nama Anggota</th>
                                    <th class="px-4 py-3">Username</th>
                                    <th class="px-4 py-3 text-center">Hak Akses</th>
                                    <th class="px-4 py-3 text-center rounded-r-lg">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach($users as $user): ?>
                                <tr class="hover:bg-slate-50/60 transition-colors <?= ($edit_id == $user['id']) ? 'bg-amber-50/40' : '' ?>">
                                    <td class="px-4 py-3.5 text-center font-mono text-xs text-slate-400"><?= $user['id'] ?></td>
                                    <td class="px-4 py-3.5 font-bold text-slate-800"><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                                    <td class="px-4 py-3.5 font-mono text-xs text-slate-600">@<?= htmlspecialchars($user['username']) ?></td>
                                    <td class="px-4 py-3.5 text-center">
                                        <?php 
                                        $role = strtolower($user['role']);
                                        if ($role == 'admin') {
                                            echo '<span class="px-2 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded-md text-xs font-bold uppercase tracking-wider">Admin</span>';
                                        } elseif ($role == 'bendahara') {
                                            echo '<span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-md text-xs font-bold uppercase tracking-wider">Kasir</span>';
                                        } else {
                                            echo '<span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-md text-xs font-bold uppercase tracking-wider">Petugas</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <a href="?edit=<?= $user['id'] ?>" 
                                               class="inline-flex items-center space-x-1 px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-100 rounded-lg text-xs font-semibold hover:bg-amber-500 hover:text-white hover:border-amber-500 transition-all">
                                                <i class="bi bi-pencil-square"></i>
                                                <span>Ubah</span>
                                            </a>

                                            <?php if($user['id'] != 1): ?>
                                                <a href="?delete=<?= $user['id'] ?>" 
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus akun staf ini?')" 
                                                   class="inline-flex items-center space-x-1 px-2.5 py-1 bg-rose-50 text-rose-600 border border-rose-100 rounded-lg text-xs font-semibold hover:bg-rose-600 hover:text-white hover:border-rose-600 transition-all">
                                                    <i class="bi bi-trash3"></i>
                                                    <span>Hapus</span>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-[11px] text-slate-400 italic font-medium px-2 py-1 bg-slate-100 rounded-md">Utama</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="mt-6 pt-4 border-t border-slate-100">
                            <a href="dashboard.php" class="px-4 py-2 bg-slate-100 text-slate-600 text-sm font-semibold rounded-xl hover:bg-slate-200 transition-colors inline-block">
                                <i class="bi bi-arrow-left mr-1"></i> Kembali ke Dashboard
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

</body>
</html>