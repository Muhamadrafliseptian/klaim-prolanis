<?php
// Mencegah error jika file ini di-include tapi session belum dimulai di file utama
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mengambil role dari session, jika tidak ada default ke empty string
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$nama_lengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'User';

// Helper function untuk mendeteksi halaman aktif agar menu menyala (highlight)
function isActive($page_name) {
    $current_script = basename($_SERVER['SCRIPT_NAME']);
    return ($current_script == $page_name) ? 'bg-slate-800 text-white border-l-4 border-emerald-500' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white';
}
?>
<aside class="w-full md:w-64 bg-slate-900 text-white flex flex-col shadow-xl flex-shrink-0">
    
    <div class="p-5 text-xl font-bold tracking-wider border-b border-slate-800 bg-slate-950 flex items-center justify-between">
        <a href="dashboard.php" class="hover:opacity-90 transition-opacity">
            <span>Klinik <span class="text-emerald-400">LAB</span></span>
        </a>
        <?php if (!empty($role)): ?>
            <span class="text-[10px] uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full font-semibold">
                <?= htmlspecialchars($role) ?>
            </span>
        <?php endif; ?>
    </div>
    
    <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto">
        <p class="text-[11px] font-semibold text-slate-500 uppercase px-3 mb-2 tracking-wider">Main Menu</p>
        
        <a href="dashboard.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-all duration-150 <?= isActive('dashboard.php') ?>">
            <i class="bi bi-speedometer2 text-lg text-indigo-400"></i>
            <span class="text-sm font-medium">Dashboard</span>
        </a>

        <a href="pasien_form.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-all duration-150 <?= isActive('pasien_form.php') ?>">
            <i class="bi bi-person-plus text-lg text-blue-400"></i>
            <span class="text-sm font-medium">Input Pasien</span>
        </a>
        
        <a href="transaksi_form.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-all duration-150 <?= isActive('transaksi_form.php') ?> <?= isActive('transaksi.php') ?>">
            <i class="bi bi-clipboard-plus text-lg text-emerald-400"></i>
            <span class="text-sm font-medium">Transaksi Baru</span>
        </a>
        
        <a href="pasien_list.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-all duration-150 <?= isActive('pasien_list.php') ?>">
            <i class="bi bi-list-ul text-lg text-cyan-400"></i>
            <span class="text-sm font-medium">Data Pasien</span>
        </a>
        
        <a href="transaksi_list.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-all duration-150 <?= isActive('transaksi_list.php') ?>">
            <i class="bi bi-receipt text-lg text-amber-400"></i>
            <span class="text-sm font-medium">Data Transaksi</span>
        </a>
        
         <a href="setting.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-all duration-150 <?= isActive('setting.php') ?>">
            <i class="bi bi-gear text-lg text-amber-400"></i>
            <span class="text-sm font-medium">setting</span>
        </a>
        

        <?php if ($role === 'admin'): ?>
        <div class="pt-4 mt-4 border-t border-slate-800/60">
            <p class="text-[11px] font-semibold text-slate-500 uppercase px-3 mb-2 tracking-wider">Admin Panel</p>
            
            <a href="user_manage.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-all duration-150 <?= isActive('user_manage.php') ?>">
                <i class="bi bi-gear text-lg text-rose-400"></i>
                <span class="text-sm font-medium">Kelola User</span>
            </a>
            
            <a href="laporan.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-all duration-150 <?= isActive('laporan.php') ?>">
                <i class="bi bi-graph-up-arrow text-lg text-purple-400"></i>
                <span class="text-sm font-medium">Laporan Finansial</span>
            </a>
        </div>
        <?php endif; ?>
    </nav>

    <div class="p-4 border-t border-slate-800/80 bg-slate-950 flex items-center justify-between">
        <div class="flex flex-col truncate pr-2">
            <span class="text-sm font-semibold text-slate-200 truncate"><?= htmlspecialchars($nama_lengkap) ?></span>
            <span class="text-xs text-slate-400 capitalize font-medium"><?= htmlspecialchars($role) ?> Account</span>
        </div>
        <a href="logout.php" title="Keluar dari Aplikasi" 
           onclick="return confirm('Apakah Anda yakin ingin logout dari sistem?');" 
           class="p-2 bg-slate-900 text-rose-400 hover:text-white hover:bg-rose-600 rounded-lg transition-all duration-150 shadow-inner group">
            <i class="bi bi-box-arrow-right text-lg group-hover:scale-105 transition-transform"></i>
        </a>
    </div>
</aside>