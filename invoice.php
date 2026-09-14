<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$id = $_GET['id'];
$stmt = $pdo->prepare("
    SELECT t.*, p.nama as nama_pasien, p.nik, p.tanggal_lahir, p.jenis_kelamin, p.no_bpjs, p.alamat,
           u.nama_lengkap as petugas
    FROM transaksi t
    JOIN pasien p ON t.pasien_id = p.id
    JOIN users u ON t.created_by = u.id
    WHERE t.id = ?
");
$stmt->execute([$id]);
$transaksi = $stmt->fetch();

if (!$transaksi) {
    die("Transaksi tidak ditemukan");
}

$stmt = $pdo->prepare("
    SELECT d.*, j.nama_pemeriksaan 
    FROM detail_transaksi d
    JOIN jenis_pemeriksaan j ON d.pemeriksaan_id = j.id
    WHERE d.transaksi_id = ?
");
$stmt->execute([$id]);
$details = $stmt->fetchAll();

// Format tanggal ke Indonesia
$bulan = array(
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
);

$tanggal_lahir = date('d', strtotime($transaksi['tanggal_lahir'])) . ' ' . 
                 $bulan[(int)date('m', strtotime($transaksi['tanggal_lahir']))] . ' ' . 
                 date('Y', strtotime($transaksi['tanggal_lahir']));

// UPGRADE: Tanggal invoice disesuaikan dengan kolom data tgl_invoice dari database
// Jika tgl_invoice kosong, otomatis beralih ke tanggal_transaksi sebagai cadangan aman.
$tgl_rujukan = (!empty($transaksi['tgl_invoice'])) ? $transaksi['tgl_invoice'] : $transaksi['tanggal_transaksi'];
$tanggal_invoice_format = date('d', strtotime($tgl_rujukan)) . ' ' . 
                          $bulan[(int)date('m', strtotime($tgl_rujukan))] . ' ' . 
                          date('Y', strtotime($tgl_rujukan));

// Terbilang
function terbilang($angka) {
    $angka = (float)$angka;
    $bilangan = array('', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas');
    
    if ($angka < 12) {
        return $bilangan[$angka];
    } elseif ($angka < 20) {
        return $bilangan[$angka - 10] . ' Belas';
    } elseif ($angka < 100) {
        return $bilangan[floor($angka / 10)] . ' Puluh ' . $bilangan[$angka % 10];
    } elseif ($angka < 200) {
        return 'Seratus ' . terbilang($angka - 100);
    } elseif ($angka < 1000) {
        return $bilangan[floor($angka / 100)] . ' Ratus ' . terbilang($angka % 100);
    } elseif ($angka < 2000) {
        return 'Seribu ' . terbilang($angka - 1000);
    } elseif ($angka < 1000000) {
        return terbilang(floor($angka / 1000)) . ' Ribu ' . terbilang($angka % 1000);
    } elseif ($angka < 1000000000) {
        return terbilang(floor($angka / 1000000)) . ' Juta ' . terbilang($angka % 1000000);
    }
}

$total_terbilang = terbilang($transaksi['total_harga']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INVOICE - <?= htmlspecialchars($transaksi['no_invoice']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            background: #f4f6f9;
            padding: 40px 20px;
            color: #000;
            line-height: 1.3;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .invoice-container {
            width: 210mm; 
            max-width: 100%;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .invoice {
            padding: 40px 50px;
        }
        
        .button-group {
            text-align: center;
            padding: 15px;
            background: #e9ecef;
            border-bottom: 1px solid #dee2e6;
        }
        
        .btn-print {
            display: inline-block;
            background: #212529;
            color: white;
            padding: 10px 24px;
            margin: 0 5px;
            text-decoration: none;
            border-radius: 4px;
            font-family: Arial, sans-serif;
            font-size: 13px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-print:hover {
            background: #343a40;
        }
        
        .btn-back { background: #007bff; }
        .btn-back:hover { background: #0056b3; }
        
        .btn-pay { background: #28a745; }
        .btn-pay:hover { background: #218838; }
        
        .kop-surat {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 4px double #000;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }
        
        .kop-logo {
            width: 15%;
            text-align: center;
            vertical-align: middle;
            padding-right: 15px;
        }
        
        .kop-text {
            width: 85%;
            text-align: center;
            vertical-align: middle;
        }
        
        .kop-text h1 { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        .kop-text h2 { font-size: 15px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        .kop-text h3 { font-size: 14px; font-weight: bold; text-transform: uppercase; margin-bottom: 4px; }
        .kop-text p { font-size: 11px; font-style: normal; margin-bottom: 2px; }
        
        .invoice-title {
            text-align: center;
            margin: 25px 0;
        }
        
        .invoice-title h1 {
            font-size: 20px;
            font-weight: bold;
            text-decoration: underline;
            letter-spacing: 1px;
        }
        
        .invoice-title h2 {
            font-size: 16px;
            font-weight: normal;
            margin-top: 5px;
        }
        
        .patient-info {
            margin: 20px 0;
        }
        
        .patient-info table {
            width: 100%;
            border-collapse: collapse;
            margin-left: 30px; 
        }
        
        .patient-info td {
            padding: 2px 0;
            font-size: 14px;
            vertical-align: top;
        }
        
        .patient-info td.label {
            width: 200px;
        }
        
        .patient-info td.colon {
            width: 30px;
            text-align: center;
        }
        
        .table-invoice {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .table-invoice th,
        .table-invoice td {
            border: 1px solid #000;
            padding: 8px 10px;
            font-size: 13px;
        }
        
        .table-invoice th {
            background: #f2f2f2;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }
        
        .table-invoice td.center { text-align: center; width: 40px; }
        .table-invoice td.right { text-align: right; }
        
        .total-row {
            font-weight: bold;
            background: #fafafa;
        }
        
        .terbilang-box {
            font-style: italic;
            font-weight: normal;
            font-size: 12px;
            padding-top: 5px;
            display: block;
        }
        
        .signature-container {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }
        
        .signature-container td {
            width: 33.33%;
            vertical-align: top;
            text-align: center;
            font-size: 13px;
        }
        
        .signature-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
            /* Memberikan ruang tinggi agar teks jabatan dan nama tidak tumpang tindih secara berantakan */
            height: 90px; 
        }
        
        .signature-img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-height: 80px;
            width: auto;
            z-index: 1;
            /* Memastikan warna latar belakang gambar transparan jika formatnya PNG */
            mix-blend-mode: multiply; 
        }
        
        .signature-date {
            text-align: center;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 10px;
            text-align: center;
            font-size: 11px;
            border-top: 1px dashed #000;
            color: #333;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
                display: block; 
                min-height: auto;
            }
            .invoice-container {
                box-shadow: none;
                width: 100%;
            }
            .button-group {
                display: none;
            }
            .invoice {
                padding: 20px 30px;
            }
            .table-invoice th {
                background: #f2f2f2 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="button-group">
            <button onclick="window.print()" class="btn-print">
                🖨️ CETAK LAPORAN
            </button>
            <a href="dashboard.php" class="btn-print btn-back">
                ← KEMBALI
            </a>
            
            <?php if(strtolower($transaksi['status']) !== 'disetujui' && isRole('bendahara')): ?>
            <form method="POST" action="bayar.php" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui transaksi ini? Status akan diubah menjadi DISETUJUI.');">
                <input type="hidden" name="transaksi_id" value="<?= $id ?>">
                <button type="submit" name="konfirmasi_setuju" class="btn-print btn-pay">
                    ✅ SETUJUI TRANSAKSI
                </button>
            </form>
            <?php endif; ?>
        </div>
        
        <div class="invoice">
            <table class="kop-surat">
                <tr>
                    <td class="kop-logo">
                        <img src="logo_dki.png" alt="Logo DKI Jakarta" style="width: 75px; height: auto; display: block; margin: 0 auto;">
                    </td>
                    <td class="kop-text">
                        <h1>Pemerintah Provinsi Daerah Khusus Ibukota Jakarta</h1>
                        <h2>Dinas Kesehatan</h2>
                        <h3>Suku Dinas Kesehatan Kota Administrasi Jakarta Barat</h3>
                        <h2>Pusat Kesehatan Masyarakat Kebon Jeruk</h2>
                        <p>Jl. Raya Kebon Jeruk No.2, Kebon Jeruk, Kota Administrasi Jakarta Barat, DKI Jakarta 11530</p>
                        <p>Telp: 021-5309838 | Fax: 021-22530756 | Website: www.puskesmaskebonjeruk.com</p>
                    </td>
                </tr>
            </table>
            
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <h2>Nomor: <?= htmlspecialchars($transaksi['no_invoice']) ?></h2>
            </div>
            
            <div class="patient-info">
                <table>
                    <tr>
                        <td class="label">Nama Pasien</td>
                        <td class="colon">:</td>
                        <td style="font-weight: bold;"><?= htmlspecialchars($transaksi['nama_pasien']) ?></td>
                    </tr>
                    <tr>
                        <td class="label">Nomor Induk Kependudukan</td>
                        <td class="colon">:</td>
                        <td><?= htmlspecialchars($transaksi['nik']) ?></td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal Lahir / Umur</td>
                        <td class="colon">:</td>
                        <td><?= htmlspecialchars($tanggal_lahir) ?></td>
                    </tr>
                    <tr>
                        <td class="label">Jenis Kelamin</td>
                        <td class="colon">:</td>
                        <td><?= $transaksi['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                    </tr>
                    <tr>
                        <td class="label">No. Kartu BPJS</td>
                        <td class="colon">:</td>
                        <td><?= !empty($transaksi['no_bpjs']) ? htmlspecialchars($transaksi['no_bpjs']) : '-' ?></td>
                    </tr>
                    <tr>
                        <td class="label">Alamat Tinggal</td>
                        <td class="colon">:</td>
                        <td><?= htmlspecialchars($transaksi['alamat']) ?></td>
                    </tr>
                </table>
            </div>
            
            <table class="table-invoice">
                <thead>
                    <tr>
                        <th style="width: 8%;">No.</th>
                        <th style="text-align: left;">Rincian Pemeriksaan / Layanan Kesehatan</th>
                        <th style="width: 25%; text-align: right;">Jumlah Tarif (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach($details as $detail): ?>
                    <tr>
                        <td class="center"><?= $no++ ?>.</td>
                        <td><?= htmlspecialchars($detail['nama_pemeriksaan']) ?></td>
                        <td class="right"><?= number_format($detail['harga'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="2" style="text-align: right; text-transform: uppercase;">Total Keseluruhan :</td>
                        <td class="right">Rp <?= number_format($transaksi['total_harga'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <span class="terbilang-box">
                                <strong>Terbilang:</strong> # <?= ucfirst(strtolower($total_terbilang)) ?> Rupiah #
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <table class="signature-container">
                <tr>
                    <td></td>
                    <td></td>
                    <td>
                        <div class="signature-date">
                            Jakarta, <?= htmlspecialchars($tanggal_invoice_format) ?><br>
                            Bendahara Penerimaan<br>
                            Puskesmas Kebon Jeruk
                        </div>
                        
                        <div class="signature-wrapper">
                            <img src="ttd.png" alt="Tanda Tangan" class="signature-img">
                        </div>
                        
                        <div class="signature-name">Tresy Lediawaty</div>
                        <div>NIP. 199602062019032017</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>