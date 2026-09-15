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

// Query dioptimalkan dengan JOIN untuk cek transaksi sekaligus (menghindari N+1 Query Problem)
try {
    $query = "
        SELECT p.*, COUNT(t.id) as total_transaksi 
        FROM pasien p 
        LEFT JOIN transaksi t ON p.id = t.pasien_id 
        GROUP BY p.id 
        ORDER BY p.id DESC
    ";
    $pasien_list = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    $pasien_list = $pdo->query("SELECT p.*, 0 as total_transaksi FROM pasien p")->fetchAll();
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
    <title>Daftar Pasien - Klinik LAB</title>
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
                    <span class="text-slate-600 font-medium">Data Pasien</span>
                </div>
                
                <a href="pasien_form.php" 
                   class="px-3 py-1.5 bg-emerald-500 text-white text-xs font-semibold rounded-lg hover:bg-emerald-600 transition-all flex items-center space-x-1.5">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Tambah Pasien</span>
                </a>
            </div>

            <!-- Flash Message -->
            <?php if($flash_message): ?>
            <div class="p-3 rounded-lg flex items-center space-x-2 text-xs <?= $flash_type == 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
                <i class="bi <?= $flash_type == 'success' ? 'bi-check-circle-fill text-emerald-500' : 'bi-exclamation-triangle-fill text-rose-500' ?>"></i>
                <span class="font-medium"><?= htmlspecialchars($flash_message) ?></span>
            </div>
            <?php endif; ?>

            <!-- Container Tabel Mode Terang -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                
                <!-- Header Card Ringkas -->
                <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 bg-white">
                    <div class="flex items-center space-x-2">
                        <i class="bi bi-people text-emerald-600 text-lg"></i>
                        <h2 class="text-sm font-bold text-slate-800">Database Pasien Terdaftar</h2>
                    </div>
                    
                    <div class="relative w-full sm:w-56">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                            <i class="bi bi-search text-xs"></i>
                        </span>
                        <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Cari nama/NIK..." 
                               class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:bg-white focus:outline-hidden focus:border-emerald-500">
                    </div>
                </div>

                <!-- Tabel Ringkas Pas Layar -->
                <div class="w-full">
                    <table class="w-full text-xs text-left text-slate-600">
                        <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 uppercase">
                            <tr>
                                <th class="px-3 py-2.5 w-12 text-center">No</th>
                                <th class="px-3 py-2.5">Nama Pasien</th>
                                <th class="px-3 py-2.5 w-32">NIK</th>
                                <th class="px-3 py-2.5 w-24">Tgl Lahir</th>
                                <th class="px-3 py-2.5 w-12 text-center">L/P</th>
                                <th class="px-3 py-2.5 w-32">No. BPJS</th>
                                <th class="px-3 py-2.5">Alamat</th>
                                <th class="px-3 py-2.5 w-20 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody" class="divide-y divide-slate-100">
                            <?php $no = 1; foreach($pasien_list as $pasien): ?>
                            <tr class="table-row hover:bg-slate-50 transition-colors">
                                <td class="px-3 py-2.5 text-center font-medium text-slate-400"><?= $no++ ?></td>
                                <td class="px-3 py-2.5 font-bold text-slate-800"><?= htmlspecialchars($pasien['nama']) ?></td>
                                <td class="px-3 py-2.5 font-mono text-slate-600"><?= htmlspecialchars($pasien['nik']) ?></td>
                                <td class="px-3 py-2.5 text-slate-500">
                                    <?= date('d/m/Y', strtotime($pasien['tanggal_lahir'])) ?>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <?php if($pasien['jenis_kelamin'] == 'L'): ?>
                                        <span class="px-1.5 py-0.5 bg-blue-50 text-blue-600 font-bold rounded">L</span>
                                    <?php else: ?>
                                        <span class="px-1.5 py-0.5 bg-pink-50 text-pink-600 font-bold rounded">P</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2.5">
                                    <?= $pasien['no_bpjs'] ? htmlspecialchars($pasien['no_bpjs']) : '<span class="text-slate-400 italic">Umum</span>' ?>
                                </td>
                                <td class="px-3 py-2.5 text-slate-500 max-w-[150px] truncate" title="<?= htmlspecialchars($pasien['alamat']) ?>">
                                    <?= htmlspecialchars($pasien['alamat']) ?>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <div class="flex items-center justify-center space-x-1">
                                        <a href="pasien_edit.php?id=<?= $pasien['id'] ?>" 
                                           class="p-1.5 text-slate-600 hover:text-amber-600 hover:bg-amber-50 rounded-md transition-colors" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if($pasien['total_transaksi'] > 0): ?>
                                            <span class="p-1.5 text-slate-300 cursor-not-allowed" title="Pasien memiliki transaksi terkait">
                                                <i class="bi bi-lock"></i>
                                            </span>
                                        <?php else: ?>
                                            <button onclick="confirmDelete(<?= $pasien['id'] ?>, '<?= addslashes($pasien['nama']) ?>')" 
                                                    class="p-1.5 text-slate-600 hover:text-rose-600 hover:bg-rose-50 rounded-md transition-colors" title="Hapus">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination Ringan -->
                    <div class="p-3 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500 bg-white">
                        <div>
                            Menampilkan <span id="pageInfo" class="font-bold text-slate-800">1 - 10</span> dari <span class="font-bold text-slate-800"><?= count($pasien_list) ?></span>
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
                <h3 class="text-sm font-bold text-slate-800">Hapus Pasien?</h3>
                <p class="text-xs font-bold text-slate-700 bg-slate-100 p-2 rounded-lg" id="deletePasienText">-</p>
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

            let paginationHTML = '';
            paginationHTML += `<button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="px-2 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 disabled:opacity-40">Prev</button>`;
            
            for (let i = 1; i <= totalPages; i++) {
                if (i === currentPage) {
                    paginationHTML += `<button class="px-2.5 py-1 rounded bg-emerald-500 text-white font-bold">${i}</button>`;
                } else {
                    paginationHTML += `<button onclick="changePage(${i})" class="px-2 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100">${i}</button>`;
                }
            }
            
            paginationHTML += `<button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} class="px-2 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 disabled:opacity-40">Next</button>`;
            
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

        function confirmDelete(id, nama) {
            document.getElementById('deletePasienText').innerText = nama;
            document.getElementById('deleteConfirmLink').href = 'pasien_list.php?delete=' + id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</body>
</html>