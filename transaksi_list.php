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
        
        $stmt = $pdo->prepare("DELETE FROM detail_transaksi WHERE transaksi_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        
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
    $query = "
        SELECT t.*, p.nama as nama_pasien 
        FROM transaksi t
        JOIN pasien p ON t.pasien_id = p.id
    ";
    $transaksi_list = $pdo->query($query)->fetchAll();
}

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
            <div class="p-4 rounded-xl flex items-center space-x-3 text-sm <?= $flash_type == 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
                <i class="bi <?= $flash_type == 'success' ? 'bi-check-circle-fill text-emerald-500' : 'bi-exclamation-triangle-fill text-rose-500' ?> text-lg"></i>
                <span class="font-medium"><?= htmlspecialchars($flash_message) ?></span>
            </div>
            <?php endif; ?>

            <!-- Container Tabel Mode Terang -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mx-auto">
                
                <!-- Header Mode Terang -->
                <div class="bg-white p-5 border-b border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center space-x-3">
                        <div class="p-2.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-xl">
                            <i class="bi bi-receipt text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 tracking-tight">Riwayat Transaksi & Invoice</h2>
                            <p class="text-xs text-slate-500">Memantau status pembayaran dan pencatatan billing pasien.</p>
                        </div>
                    </div>
                    
                    <!-- Fitur Pencarian Cepat -->
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="bi bi-search text-xs"></i>
                        </span>
                        <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Cari invoice/pasien..." 
                               class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-hidden focus:border-emerald-500 transition-all">
                    </div>
                </div>

                <div class="p-6 overflow-x-auto">
                    
                    <table class="w-full text-sm text-left text-slate-600">
                        <thead class="text-xs uppercase bg-slate-100 text-slate-700 font-bold border-y border-slate-200">
                            <tr>
                                <th class="px-4 py-3.5">No. Invoice</th>
                                <th class="px-4 py-3.5">Nama Pasien</th>
                                <th class="px-4 py-3.5">Tanggal Invoice</th>
                                <th class="px-4 py-3.5">Tanggal Periksa</th>
                                <th class="px-4 py-3.5">Total Harga</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                                <th class="px-4 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody" class="divide-y divide-slate-100">
                            <?php foreach($transaksi_list as $trx): ?>
                            <tr class="table-row hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-4 font-mono text-xs font-bold text-slate-800">
                                    <?= htmlspecialchars($trx['no_invoice']) ?>
                                </td>
                                
                                <td class="px-4 py-4 font-semibold text-slate-800">
                                    <?= htmlspecialchars($trx['nama_pasien']) ?>
                                </td>
                                
                                <td class="px-4 py-4 text-slate-600 whitespace-nowrap">
                                    <i class="bi bi-calendar3 text-slate-400 mr-1"></i>
                                    <?= $trx['tgl_invoice'] ? date('d/m/Y', strtotime($trx['tgl_invoice'])) : '-' ?>
                                </td>
                                
                                <td class="px-4 py-4 text-slate-600 whitespace-nowrap">
                                    <i class="bi bi-clock text-slate-400 mr-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($trx['tanggal_transaksi'])) ?>
                                </td>
                                
                                <td class="px-4 py-4 font-bold text-slate-900">
                                    Rp <?= number_format($trx['total_harga'], 0, ',', '.') ?>
                                </td>
                                
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <?php if(strtolower($trx['status']) == 'disetujui'): ?>
                                        <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-lg text-[11px] font-bold uppercase tracking-wider">
                                            DISETUJUI
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 bg-amber-100 text-amber-800 border border-amber-200 rounded-lg text-[11px] font-bold uppercase tracking-wider">
                                            PENDING
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <a href="invoice.php?id=<?= $trx['id'] ?>" 
                                           class="p-2 bg-slate-100 text-slate-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg text-xs font-bold transition-all border border-slate-200"
                                           title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="transaksi_edit.php?id=<?= $trx['id'] ?>" 
                                           class="p-2 bg-slate-100 text-slate-600 hover:text-amber-600 hover:bg-amber-50 rounded-lg text-xs font-bold transition-all border border-slate-200"
                                           title="Edit Transaksi">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?= $trx['id'] ?>, '<?= addslashes($trx['no_invoice']) ?>')" 
                                                class="p-2 bg-slate-100 text-slate-600 hover:text-rose-600 hover:bg-rose-50 rounded-lg text-xs font-bold transition-all border border-slate-200"
                                                title="Hapus Transaksi">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination Ringan (Fast & Clean) -->
                    <div class="mt-6 pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                        <div>
                            Menampilkan <span id="pageInfo" class="font-bold text-slate-800">1 - 10</span> dari <span class="font-bold text-slate-800"><?= count($transaksi_list) ?></span> data
                        </div>
                        <div class="flex items-center space-x-1" id="paginationControls">
                            <!-- Tombol Pagination di-render via JavaScript -->
                        </div>
                    </div>

                </div>
            </div>

        </main>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="deleteModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" onclick="closeDeleteModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <div class="bg-rose-50 p-5 border-b border-rose-100">
                    <h3 class="text-base font-bold text-rose-800 flex items-center space-x-2">
                        <i class="bi bi-exclamation-triangle-fill text-rose-600"></i>
                        <span>Konfirmasi Hapus Data</span>
                    </h3>
                </div>
                
                <div class="p-6 space-y-4">
                    <p class="text-slate-600 text-sm">
                        Apakah Anda yakin ingin menghapus transaksi dengan invoice:
                    </p>
                    <p class="text-base font-bold text-slate-900 font-mono bg-slate-50 p-3 rounded-xl text-center border border-slate-200" id="deleteInvoiceText">
                        -
                    </p>
                    
                    <div class="flex items-center space-x-3 pt-3">
                        <button onclick="closeDeleteModal()" 
                                class="flex-1 px-4 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-slate-200 transition-colors">
                            Batal
                        </button>
                        <a href="#" id="deleteConfirmLink" 
                           class="flex-1 px-4 py-2.5 bg-rose-500 text-white rounded-xl text-xs font-bold hover:bg-rose-600 transition-colors text-center">
                            Hapus
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pagination & Searching Ringan Pure JS (Tanpa CDN Berat)
        const rowsPerPage = 10;
        let currentPage = 1;
        const allRows = Array.from(document.querySelectorAll('.table-row'));
        let filteredRows = [...allRows];

        function renderTable() {
            const totalPages = Math.ceil(filteredRows.length / rowsPerPage) || 1;
            if (currentPage > totalPages) currentPage = totalPages;

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            allRows.forEach(row => row.style.display = 'none');
            filteredRows.slice(start, end).forEach(row => row.style.display = '');

            // Update Text Info
            const countStart = filteredRows.length > 0 ? start + 1 : 0;
            const countEnd = Math.min(end, filteredRows.length);
            document.getElementById('pageInfo').innerText = `${countStart} - ${countEnd}`;

            // Render Pagination Buttons
            let paginationHTML = '';
            paginationHTML += `<button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 disabled:opacity-40 disabled:hover:bg-white font-semibold">Prev</button>`;
            
            for (let i = 1; i <= totalPages; i++) {
                if (i === currentPage) {
                    paginationHTML += `<button class="px-3 py-1.5 rounded-lg bg-emerald-500 text-white font-bold">${i}</button>`;
                } else {
                    paginationHTML += `<button onclick="changePage(${i})" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 font-semibold">${i}</button>`;
                }
            }
            
            paginationHTML += `<button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 disabled:opacity-40 disabled:hover:bg-white font-semibold">Next</button>`;
            
            document.getElementById('paginationControls').innerHTML = paginationHTML;
        }

        function changePage(page) {
            const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            renderTable();
        }

        function searchTable() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            filteredRows = allRows.filter(row => row.innerText.toLowerCase().includes(query));
            currentPage = 1;
            renderTable();
        }

        // Jalankan awal
        renderTable();

        // Modal Functionality
        function confirmDelete(id, invoice) {
            document.getElementById('deleteInvoiceText').innerText = invoice;
            document.getElementById('deleteConfirmLink').href = 'transaksi_list.php?delete=' + id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</body>
</html>