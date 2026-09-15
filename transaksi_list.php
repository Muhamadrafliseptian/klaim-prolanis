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

        <main class="flex-1 p-4 md:p-6 space-y-4 min-w-0">
            
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center space-x-2 text-xs text-slate-400">
                    <a href="dashboard.php" class="hover:text-emerald-500">Dashboard</a>
                    <span>/</span>
                    <span class="text-slate-600 font-medium">Daftar Transaksi</span>
                </div>
                
                <a href="transaksi_form.php" 
                   class="px-3 py-1.5 bg-emerald-500 text-white text-xs font-semibold rounded-lg hover:bg-emerald-600 transition-all flex items-center space-x-1.5">
                    <i class="bi bi-plus-lg"></i>
                    <span>Transaksi Baru</span>
                </a>
            </div>

            <!-- Flash Message -->
            <?php if($flash_message): ?>
            <div id="flashAlert" class="p-3 rounded-lg flex items-center space-x-2 text-xs transition-opacity duration-300 <?= $flash_type == 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
                <i class="bi <?= $flash_type == 'success' ? 'bi-check-circle-fill text-emerald-500' : 'bi-exclamation-triangle-fill text-rose-500' ?>"></i>
                <span class="font-medium"><?= htmlspecialchars($flash_message) ?></span>
                <button onclick="this.parentElement.remove()" class="ml-auto text-slate-400 hover:text-slate-600">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <?php endif; ?>

            <!-- Container Mode Terang -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                
                <!-- Header Mode Terang Ringkas -->
                <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 bg-white">
                    <div class="flex items-center space-x-2">
                        <i class="bi bi-receipt text-emerald-600 text-lg"></i>
                        <h2 class="text-sm font-bold text-slate-800">Riwayat Transaksi & Invoice</h2>
                    </div>
                    
                    <div class="relative w-full sm:w-56">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                            <i class="bi bi-search text-xs"></i>
                        </span>
                        <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Cari invoice/pasien..." 
                               class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:bg-white focus:outline-hidden focus:border-emerald-500">
                    </div>
                </div>

                <!-- Tabel Ringkas Pas Layar -->
                <div class="w-full">
                    <table class="w-full text-xs text-left text-slate-600">
                        <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 uppercase">
                            <tr>
                                <th class="px-3 py-2.5 w-32">No. Invoice</th>
                                <th class="px-3 py-2.5">Nama Pasien</th>
                                <th class="px-3 py-2.5 w-36">Tgl Inv / Periksa</th>
                                <th class="px-3 py-2.5 w-28">Total</th>
                                <th class="px-3 py-2.5 w-24 text-center">Status</th>
                                <th class="px-3 py-2.5 w-24 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody" class="divide-y divide-slate-100">
                            <?php foreach($transaksi_list as $trx): ?>
                            <tr class="table-row hover:bg-slate-50 transition-colors">
                                <td class="px-3 py-2.5 font-mono font-bold text-slate-800">
                                    <?= htmlspecialchars($trx['no_invoice']) ?>
                                </td>
                                
                                <td class="px-3 py-2.5 font-semibold text-slate-800">
                                    <?= htmlspecialchars($trx['nama_pasien']) ?>
                                </td>
                                
                                <td class="px-3 py-2.5 text-slate-500 leading-tight">
                                    <div>Inv: <?= $trx['tgl_invoice'] ? date('d/m/Y', strtotime($trx['tgl_invoice'])) : '-' ?></div>
                                    <div class="text-[10px] text-slate-400">Prk: <?= date('d/m/Y H:i', strtotime($trx['tanggal_transaksi'])) ?></div>
                                </td>
                                
                                <td class="px-3 py-2.5 font-bold text-slate-900">
                                    Rp <?= number_format($trx['total_harga'], 0, ',', '.') ?>
                                </td>
                                
                                <td class="px-3 py-2.5 text-center">
                                    <?php if(strtolower($trx['status']) == 'disetujui'): ?>
                                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-md text-[10px] font-bold">
                                            DISETUJUI
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-md text-[10px] font-bold">
                                            PENDING
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-3 py-2.5 text-center">
                                    <div class="flex items-center justify-center space-x-1">
                                        <a href="invoice.php?id=<?= $trx['id'] ?>" 
                                           class="p-1.5 text-slate-600 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors" title="Lihat">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="transaksi_edit.php?id=<?= $trx['id'] ?>" 
                                           class="p-1.5 text-slate-600 hover:text-amber-600 hover:bg-amber-50 rounded-md transition-colors" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?= $trx['id'] ?>, '<?= addslashes($trx['no_invoice']) ?>')" 
                                                class="p-1.5 text-slate-600 hover:text-rose-600 hover:bg-rose-50 rounded-md transition-colors" title="Hapus">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination Ringkas (Maksimal 5 Nomor) -->
                    <div class="p-3 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500 bg-white">
                        <div>
                            Menampilkan <span id="pageInfo" class="font-bold text-slate-800">1 - 10</span> dari <span class="font-bold text-slate-800"><?= count($transaksi_list) ?></span>
                        </div>
                        <div class="flex items-center space-x-1" id="paginationControls"></div>
                    </div>

                </div>
            </div>

        </main>
    </div>

    <!-- Modal Hapus Ringkas -->
    <div id="deleteModal" class="fixed inset-0 z-50 overflow-y-auto hidden" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeDeleteModal()"></div>
            <div class="relative bg-white rounded-xl max-w-sm w-full p-5 border border-slate-200 shadow-lg text-center space-y-3">
                <i class="bi bi-exclamation-triangle-fill text-rose-500 text-3xl"></i>
                <h3 class="text-sm font-bold text-slate-800">Hapus Transaksi?</h3>
                <p class="text-xs font-mono font-bold text-slate-700 bg-slate-100 p-2 rounded-lg" id="deleteInvoiceText">-</p>
                <div class="flex items-center space-x-2 pt-2">
                    <button onclick="closeDeleteModal()" class="flex-1 py-1.5 bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold">Batal</button>
                    <a href="#" id="deleteConfirmLink" class="flex-1 py-1.5 bg-rose-500 text-white rounded-lg text-xs font-semibold">Hapus</a>
                </div>
            </div>
        </div>
    </div>

    <script>
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

            const countStart = filteredRows.length > 0 ? start + 1 : 0;
            const countEnd = Math.min(end, filteredRows.length);
            document.getElementById('pageInfo').innerText = `${countStart} - ${countEnd}`;

            // --- RENDER PAGINATION RINGKAS ---
            let paginationHTML = '';
            
            // Tombol Prev
            paginationHTML += `<button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="px-2 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 disabled:opacity-40 text-xs">Prev</button>`;

            // Batasi Nomor Halaman
            let startPage = Math.max(1, currentPage - 1);
            let endPage = Math.min(totalPages, currentPage + 1);

            if (currentPage === 1) {
                endPage = Math.min(totalPages, 3);
            } else if (currentPage === totalPages) {
                startPage = Math.max(1, totalPages - 2);
            }

            if (startPage > 1) {
                paginationHTML += `<button onclick="changePage(1)" class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 font-semibold text-xs">1</button>`;
                if (startPage > 2) {
                    paginationHTML += `<span class="px-1 text-slate-400 text-xs">...</span>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    paginationHTML += `<button class="px-2.5 py-1 rounded bg-emerald-500 text-white font-bold text-xs">${i}</button>`;
                } else {
                    paginationHTML += `<button onclick="changePage(${i})" class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 font-semibold text-xs">${i}</button>`;
                }
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    paginationHTML += `<span class="px-1 text-slate-400 text-xs">...</span>`;
                }
                paginationHTML += `<button onclick="changePage(${totalPages})" class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 font-semibold text-xs">${totalPages}</button>`;
            }

            // Tombol Next
            paginationHTML += `<button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} class="px-2 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 disabled:opacity-40 text-xs">Next</button>`;
            
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

        renderTable();

        function confirmDelete(id, invoice) {
            document.getElementById('deleteInvoiceText').innerText = invoice;
            document.getElementById('deleteConfirmLink').href = 'transaksi_list.php?delete=' + id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        setTimeout(function() {
            const flash = document.getElementById('flashAlert');
            if (flash) {
                flash.style.opacity = '0';
                setTimeout(() => flash.remove(), 300);
            }
        }, 4000);
    </script>
</body>
</html>