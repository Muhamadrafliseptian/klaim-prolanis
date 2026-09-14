<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Proses hapus data
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        // Cek apakah pasien memiliki transaksi terkait
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transaksi WHERE pasien_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            throw new Exception("Pasien memiliki transaksi terkait. Tidak dapat dihapus.");
        }
        
        // Hapus pasien
        $stmt = $pdo->prepare("DELETE FROM pasien WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['flash_message'] = 'Data pasien berhasil dihapus!';
        $_SESSION['flash_type'] = 'success';
    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'Gagal menghapus data: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
    }
    
    redirect('pasien_list.php');
}

// Mengantisipasi error jika kolom created_at belum ada, gunakan query aman
try {
    $pasien_list = $pdo->query("SELECT * FROM pasien ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    $pasien_list = $pdo->query("SELECT * FROM pasien")->fetchAll();
}

// Ambil flash message dari session
$flash_message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : '';
$flash_type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : '';
unset($_SESSION['flash_message']);
unset($_SESSION['flash_type']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pasien - Klinik LAB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.5/css/dataTables.tailwindcss.min.css" rel="stylesheet">
    
    <style>
        /* Custom adjustment agar DataTables menyatu dengan Tailwind */
        .dataTables_wrapper .dataTables_length select {
            padding-right: 2rem !important;
            border-radius: 0.5rem !important;
        }
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 0.5rem !important;
            padding: 0.4rem 0.8rem !important;
        }
        
        /* Modal backdrop */
        .modal-backdrop {
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
        }
        
        /* Animasi flash message */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .flash-message {
            animation: slideDown 0.3s ease-out;
        }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased">

    <div class="flex flex-col md:flex-row min-h-screen">
        
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 p-6 md:p-8 space-y-6 min-w-0">
            
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-2 text-sm text-slate-400">
                    <a href="dashboard.php" class="hover:text-emerald-500 transition-colors">Dashboard</a>
                    <span>/</span>
                    <span class="text-slate-600 font-medium">Data Pasien</span>
                </div>
                
                <a href="pasien_form.php" 
                   class="px-4 py-2 bg-emerald-500 text-white text-sm font-semibold rounded-xl hover:bg-emerald-600 shadow-xs transition-all flex items-center space-x-2">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Tambah Pasien Baru</span>
                </a>
            </div>

            <!-- Flash Message -->
            <?php if($flash_message): ?>
            <div class="flash-message p-4 rounded-xl flex items-center space-x-3 text-sm <?= $flash_type == 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
                <i class="bi <?= $flash_type == 'success' ? 'bi-check-circle-fill text-emerald-500' : 'bi-exclamation-triangle-fill text-rose-500' ?> text-lg"></i>
                <span class="font-medium"><?= htmlspecialchars($flash_message) ?></span>
                <button onclick="this.parentElement.remove()" class="ml-auto text-slate-400 hover:text-slate-600">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                
                <div class="bg-gradient-to-r from-slate-900 to-slate-800 p-5 text-white flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 rounded-xl">
                            <i class="bi bi-list-ul text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold tracking-wide">Database Pasien Terdaftar</h2>
                            <p class="text-xs text-slate-400">Total rekam medis pasien yang terdata di sistem laboratorium klinik.</p>
                        </div>
                    </div>
                    <span class="text-xs bg-white/10 px-3 py-1 rounded-full text-slate-300">
                        <?= count($pasien_list) ?> Pasien
                    </span>
                </div>

                <div class="p-6 overflow-x-auto">
                    
                    <table id="pasienTable" class="w-full text-sm text-left text-slate-600 display border-collapse">
                        <thead class="text-xs uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 text-center rounded-l-lg">No</th>
                                <th class="px-4 py-3">Nama Lengkap</th>
                                <th class="px-4 py-3">NIK</th>
                                <th class="px-4 py-3">Tanggal Lahir</th>
                                <th class="px-4 py-3 text-center">L/P</th>
                                <th class="px-4 py-3">No. BPJS</th>
                                <th class="px-4 py-3">Alamat Rumah</th>
                                <th class="px-4 py-3 text-center rounded-r-lg">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php $no = 1; foreach($pasien_list as $pasien): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-3.5 text-center font-medium text-slate-400"><?= $no++ ?></td>
                                <td class="px-4 py-3.5 font-bold text-slate-800"><?= htmlspecialchars($pasien['nama']) ?></td>
                                <td class="px-4 py-3.5 font-mono text-xs tracking-wider text-slate-600"><?= htmlspecialchars($pasien['nik']) ?></td>
                                <td class="px-4 py-3.5 text-slate-500">
                                    <i class="bi bi-calendar-event text-slate-400 mr-1"></i>
                                    <?= date('d/m/Y', strtotime($pasien['tanggal_lahir'])) ?>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <?php if($pasien['jenis_kelamin'] == 'L'): ?>
                                        <span class="px-2 py-0.5 bg-blue-50 text-blue-600 border border-blue-200 rounded-md text-xs font-semibold">L</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 bg-pink-50 text-pink-600 border border-pink-200 rounded-md text-xs font-semibold">P</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3.5">
                                    <?= $pasien['no_bpjs'] ? '<span class="text-slate-700 font-medium">'.htmlspecialchars($pasien['no_bpjs']).'</span>' : '<span class="text-slate-400 italic">Umum (Non-BPJS)</span>' ?>
                                </td>
                                <td class="px-4 py-3.5 text-slate-500 max-w-xs truncate" title="<?= htmlspecialchars($pasien['alamat']) ?>">
                                    <?= htmlspecialchars($pasien['alamat']) ?>
                                </td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <a href="pasien_edit.php?id=<?= $pasien['id'] ?>" 
                                           class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-amber-50 text-amber-700 hover:text-white hover:bg-amber-600 rounded-lg text-xs font-bold transition-all border border-amber-200 hover:border-amber-600"
                                           title="Edit Pasien">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php
                                        // Cek apakah pasien memiliki transaksi
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transaksi WHERE pasien_id = ?");
                                        $stmt->execute([$pasien['id']]);
                                        $has_transaksi = $stmt->fetchColumn() > 0;
                                        ?>
                                        <?php if($has_transaksi): ?>
                                            <span class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-slate-100 text-slate-400 rounded-lg text-xs font-bold border border-slate-200 cursor-not-allowed" title="Pasien memiliki transaksi terkait">
                                                <i class="bi bi-lock"></i>
                                            </span>
                                        <?php else: ?>
                                            <button onclick="confirmDelete(<?= $pasien['id'] ?>, '<?= addslashes($pasien['nama']) ?>')" 
                                                    class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-rose-50 text-rose-700 hover:text-white hover:bg-rose-600 rounded-lg text-xs font-bold transition-all border border-rose-200 hover:border-rose-600"
                                                    title="Hapus Pasien">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4">
                        <a href="dashboard.php" class="px-4 py-2 bg-slate-100 text-slate-600 text-sm font-semibold rounded-xl hover:bg-slate-200 transition-colors inline-block">
                            <i class="bi bi-arrow-left mr-1"></i> Kembali ke Dashboard
                        </a>
                        <a href="pasien_form.php" 
                           class="px-4 py-2 bg-emerald-500 text-white text-sm font-semibold rounded-xl hover:bg-emerald-600 transition-colors inline-block">
                            <i class="bi bi-person-plus-fill mr-1"></i> Tambah Pasien
                        </a>
                    </div>

                </div>
            </div>

        </main>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="deleteModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 modal-backdrop transition-opacity" onclick="closeDeleteModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                <div class="bg-rose-50 px-6 py-4 border-b border-rose-200">
                    <h3 class="text-lg font-bold text-rose-800 flex items-center space-x-2">
                        <i class="bi bi-exclamation-triangle-fill text-rose-600"></i>
                        <span>Konfirmasi Hapus</span>
                    </h3>
                </div>
                
                <div class="p-6 space-y-4">
                    <p class="text-slate-700">
                        Apakah Anda yakin ingin menghapus pasien:
                    </p>
                    <p class="text-lg font-bold text-slate-900 bg-slate-50 p-3 rounded-xl text-center border border-slate-200" id="deletePasienText">
                        -
                    </p>
                    <p class="text-sm text-rose-600">
                        <i class="bi bi-info-circle"></i>
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                    
                    <div class="flex items-center space-x-3 pt-2">
                        <button onclick="closeDeleteModal()" 
                                class="flex-1 px-4 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-200 transition-colors">
                            Batal
                        </button>
                        <a href="#" id="deleteConfirmLink" 
                           class="flex-1 px-4 py-2.5 bg-rose-500 text-white rounded-xl text-sm font-semibold hover:bg-rose-600 transition-colors text-center">
                            <i class="bi bi-trash3 mr-1"></i> Hapus
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.tailwindcss.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#pasienTable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.5/i18n/id.json"
                },
                "pageLength": 10,
                "responsive": true
            });
        });

        function confirmDelete(id, nama) {
            document.getElementById('deletePasienText').innerText = nama;
            document.getElementById('deleteConfirmLink').href = 'pasien_list.php?delete=' + id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modal jika klik di luar
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('deleteModal');
            if (e.target === modal || e.target.classList.contains('modal-backdrop')) {
                closeDeleteModal();
            }
        });

        // Flash message auto dismiss setelah 5 detik
        setTimeout(function() {
            const flash = document.querySelector('.flash-message');
            if (flash) {
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