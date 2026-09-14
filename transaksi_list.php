<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Proses hapus data
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        $pdo->beginTransaction();
        
        // Hapus detail transaksi terlebih dahulu
        $stmt = $pdo->prepare("DELETE FROM detail_transaksi WHERE transaksi_id = ?");
        $stmt->execute([$id]);
        
        // Hapus transaksi utama
        $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        
        // Set pesan sukses
        $_SESSION['flash_message'] = 'Data transaksi berhasil dihapus!';
        $_SESSION['flash_type'] = 'success';
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = 'Gagal menghapus data: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
    }
    
    redirect('transaksi_list.php');
}

// Ambil data transaksi gabung dengan data pasien
try {
    $query = "
        SELECT t.*, p.nama as nama_pasien 
        FROM transaksi t
        JOIN pasien p ON t.pasien_id = p.id
        ORDER BY t.id DESC
    ";
    $transaksi_list = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    // Fallback jika t.created_at belum ada atau bermasalah
    $query = "
        SELECT t.*, p.nama as nama_pasien 
        FROM transaksi t
        JOIN pasien p ON t.pasien_id = p.id
    ";
    $transaksi_list = $pdo->query($query)->fetchAll();
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
    <title>Daftar Transaksi - Klinik LAB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.5/css/dataTables.tailwindcss.min.css" rel="stylesheet">
    
    <style>
        /* Custom adjustment DataTables agar serasi dengan Tailwind */
        .dataTables_wrapper .dataTables_length select {
            padding-right: 2.5rem !important;
            border-radius: 0.75rem !important;
            border-color: #e2e8f0 !important;
        }
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 0.75rem !important;
            padding: 0.5rem 1rem !important;
            border-color: #e2e8f0 !important;
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
                    <span class="text-slate-600 font-medium">Daftar Transaksi</span>
                </div>
                
                <a href="transaksi_form.php" 
                   class="px-4 py-2 bg-emerald-500 text-white text-sm font-semibold rounded-xl hover:bg-emerald-600 shadow-xs transition-all flex items-center space-x-2">
                    <i class="bi bi-plus-lg"></i>
                    <span>Transaksi Baru</span>
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

            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden mx-auto">
                
                <div class="bg-gradient-to-r from-slate-900 to-slate-800 p-5 text-white flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-amber-500/10 text-amber-400 border border-amber-500/20 rounded-xl">
                            <i class="bi bi-receipt text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold tracking-wide">Riwayat Transaksi & Invoice</h2>
                            <p class="text-xs text-slate-400">Memantau status pembayaran, data invoice billing, dan penanganan billing pasien.</p>
                        </div>
                    </div>
                    <span class="text-xs bg-white/10 px-3 py-1 rounded-full text-slate-300">
                        <?= count($transaksi_list) ?> Transaksi
                    </span>
                </div>

                <div class="p-6 overflow-x-auto">
                    
                    <table id="transaksiTable" class="w-full text-sm text-left text-slate-600 display border-collapse">
                        <thead class="text-xs uppercase bg-slate-50 text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3.5 rounded-l-lg">No. Invoice</th>
                                <th class="px-4 py-3.5">Nama Pasien</th>
                                <th class="px-4 py-3.5">Tanggal Invoice</th>
                                <th class="px-4 py-3.5">Tanggal Periksa</th>
                                <th class="px-4 py-3.5">Total Harga</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                                <th class="px-4 py-3.5 text-center rounded-r-lg">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach($transaksi_list as $trx): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-4 font-mono text-xs font-bold tracking-wider text-slate-700">
                                    <?= htmlspecialchars($trx['no_invoice']) ?>
                                </td>
                                
                                <td class="px-4 py-4 font-bold text-slate-800">
                                    <?= htmlspecialchars($trx['nama_pasien']) ?>
                                </td>
                                
                                <td class="px-4 py-4 text-slate-500 whitespace-nowrap">
                                    <i class="bi bi-calendar3 text-slate-400 mr-1"></i>
                                    <?= $trx['tgl_invoice'] ? date('d/m/Y', strtotime($trx['tgl_invoice'])) : '-' ?>
                                </td>
                                
                                <td class="px-4 py-4 text-slate-500 whitespace-nowrap">
                                    <i class="bi bi-clock text-slate-400 mr-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($trx['tanggal_transaksi'])) ?>
                                </td>
                                
                                <td class="px-4 py-4 font-semibold text-slate-900">
                                    Rp <?= number_format($trx['total_harga'], 0, ',', '.') ?>
                                </td>
                                
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <?php if(strtolower($trx['status']) == 'disetujui'): ?>
                                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold uppercase tracking-wider inline-flex items-center space-x-1">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                            <span>DISETUJUI</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold uppercase tracking-wider inline-flex items-center space-x-1">
                                            <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                                            <span>PENDING</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <a href="invoice.php?id=<?= $trx['id'] ?>" 
                                           class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-blue-50 text-blue-700 hover:text-white hover:bg-blue-600 rounded-lg text-xs font-bold transition-all border border-blue-200 hover:border-blue-600"
                                           title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="transaksi_edit.php?id=<?= $trx['id'] ?>" 
                                           class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-amber-50 text-amber-700 hover:text-white hover:bg-amber-600 rounded-lg text-xs font-bold transition-all border border-amber-200 hover:border-amber-600"
                                           title="Edit Transaksi">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?= $trx['id'] ?>, '<?= addslashes($trx['no_invoice']) ?>')" 
                                                class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-rose-50 text-rose-700 hover:text-white hover:bg-rose-600 rounded-lg text-xs font-bold transition-all border border-rose-200 hover:border-rose-600"
                                                title="Hapus Transaksi">
                                            <i class="bi bi-trash3"></i>
                                        </button>
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
                        <a href="transaksi_form.php" 
                           class="px-4 py-2 bg-emerald-500 text-white text-sm font-semibold rounded-xl hover:bg-emerald-600 transition-colors inline-block">
                            <i class="bi bi-plus-lg mr-1"></i> Transaksi Baru
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
                        Apakah Anda yakin ingin menghapus transaksi dengan invoice:
                    </p>
                    <p class="text-lg font-bold text-slate-900 font-mono bg-slate-50 p-3 rounded-xl text-center border border-slate-200" id="deleteInvoiceText">
                        -
                    </p>
                    <p class="text-sm text-rose-600">
                        <i class="bi bi-info-circle"></i>
                        Tindakan ini tidak dapat dibatalkan dan akan menghapus semua detail transaksi terkait.
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
            $('#transaksiTable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.5/i18n/id.json"
                },
                "pageLength": 10,
                "responsive": true,
                "order": [] // Menonaktifkan auto-sort default
            });
        });

        function confirmDelete(id, invoice) {
            document.getElementById('deleteInvoiceText').innerText = invoice;
            document.getElementById('deleteConfirmLink').href = 'transaksi_list.php?delete=' + id;
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