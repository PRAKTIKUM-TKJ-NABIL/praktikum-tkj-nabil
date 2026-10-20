<?php
require_once __DIR__ . '/config/database.php';
require_once 'includes/auth_check.php';

// ===== LOAD LANGUAGE =====
require_once 'includes/lang.php';
require_once 'includes/functions.php';

// Load language
$lang = $_SESSION['language'] ?? 'en';
$lang_data = loadLanguage($lang);

include 'includes/header.php';

// ===== FILTER =====
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'monthly'; // monthly atau daily
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$tahun = substr($bulan, 0, 4);
$bulan_angka = substr($bulan, 5, 2);

// ===== AMBIL DATA TRANSAKSI BERDASARKAN FILTER =====
if ($filter_type == 'daily') {
    // Filter harian
    $query = "SELECT *, 
              CASE 
                  WHEN dari_tabungan = 1 AND jenis_tabungan = 'ditabung' THEN 'Ditabung'
                  WHEN dari_tabungan = 1 AND jenis_tabungan = 'diambil' THEN 'Diambil'
                  ELSE jenis 
              END as jenis_display,
              CASE 
                  WHEN dari_tabungan = 1 THEN 'Tabungan'
                  ELSE kategori 
              END as kategori_display
              FROM catatan_keuangan 
              WHERE user_id = ? AND tanggal = ? 
              ORDER BY tanggal DESC, created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    // Hitung total untuk hari tersebut
    $query_total = "SELECT 
                        SUM(CASE WHEN jenis='pemasukan' AND dari_tabungan=0 THEN jumlah ELSE 0 END) as total_pemasukan,
                        SUM(CASE WHEN jenis='pengeluaran' AND dari_tabungan=0 THEN jumlah ELSE 0 END) as total_pengeluaran,
                        SUM(CASE WHEN dari_tabungan=1 AND jenis_tabungan='ditabung' THEN jumlah ELSE 0 END) as total_ditabung,
                        SUM(CASE WHEN dari_tabungan=1 AND jenis_tabungan='diambil' THEN jumlah ELSE 0 END) as total_diambil
                    FROM catatan_keuangan 
                    WHERE user_id = ? AND tanggal = ?";
    $stmt = $conn->prepare($query_total);
    $stmt->bind_param("is", $user_id, $tanggal);
    $stmt->execute();
    $total_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $label_filter = date('d F Y', strtotime($tanggal));
    
} else {
    // Filter bulanan (default)
    $query = "SELECT *, 
              CASE 
                  WHEN dari_tabungan = 1 AND jenis_tabungan = 'ditabung' THEN 'Ditabung'
                  WHEN dari_tabungan = 1 AND jenis_tabungan = 'diambil' THEN 'Diambil'
                  ELSE jenis 
              END as jenis_display,
              CASE 
                  WHEN dari_tabungan = 1 THEN 'Tabungan'
                  ELSE kategori 
              END as kategori_display
              FROM catatan_keuangan 
              WHERE user_id = ? AND YEAR(tanggal) = ? AND MONTH(tanggal) = ? 
              ORDER BY tanggal DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $user_id, $tahun, $bulan_angka);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    // Hitung total untuk bulan tersebut
    $query_total = "SELECT 
                        SUM(CASE WHEN jenis='pemasukan' AND dari_tabungan=0 THEN jumlah ELSE 0 END) as total_pemasukan,
                        SUM(CASE WHEN jenis='pengeluaran' AND dari_tabungan=0 THEN jumlah ELSE 0 END) as total_pengeluaran,
                        SUM(CASE WHEN dari_tabungan=1 AND jenis_tabungan='ditabung' THEN jumlah ELSE 0 END) as total_ditabung,
                        SUM(CASE WHEN dari_tabungan=1 AND jenis_tabungan='diambil' THEN jumlah ELSE 0 END) as total_diambil
                    FROM catatan_keuangan 
                    WHERE user_id = ? AND YEAR(tanggal) = ? AND MONTH(tanggal) = ?";
    $stmt = $conn->prepare($query_total);
    $stmt->bind_param("iss", $user_id, $tahun, $bulan_angka);
    $stmt->execute();
    $total_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $label_filter = date('F Y', strtotime($bulan));
}

$total_pemasukan = $total_data['total_pemasukan'] ?? 0;
$total_pengeluaran = $total_data['total_pengeluaran'] ?? 0;
$total_ditabung = $total_data['total_ditabung'] ?? 0;
$total_diambil = $total_data['total_diambil'] ?? 0;

$saldo = $total_pemasukan - $total_pengeluaran - $total_ditabung + $total_diambil;
$total_tabungan_bersih = $total_ditabung - $total_diambil;

// ===== STATISTIK PER KATEGORI =====
if ($filter_type == 'daily') {
    $query_kategori = "SELECT 
                        CASE 
                            WHEN dari_tabungan=1 AND jenis_tabungan='ditabung' THEN 'Tabungan (Ditabung)'
                            WHEN dari_tabungan=1 AND jenis_tabungan='diambil' THEN 'Tabungan (Diambil)'
                            ELSE kategori 
                        END as kategori_display,
                        CASE 
                            WHEN dari_tabungan=1 AND jenis_tabungan='ditabung' THEN 'pengeluaran'
                            WHEN dari_tabungan=1 AND jenis_tabungan='diambil' THEN 'pemasukan'
                            ELSE jenis 
                        END as jenis_display,
                        SUM(jumlah) as total 
                       FROM catatan_keuangan 
                       WHERE user_id = ? AND tanggal = ?
                       GROUP BY kategori_display, jenis_display 
                       ORDER BY jenis_display, total DESC";
    $stmt = $conn->prepare($query_kategori);
    $stmt->bind_param("is", $user_id, $tanggal);
    $stmt->execute();
    $result_kategori = $stmt->get_result();
    $stmt->close();
} else {
    $query_kategori = "SELECT 
                        CASE 
                            WHEN dari_tabungan=1 AND jenis_tabungan='ditabung' THEN 'Tabungan (Ditabung)'
                            WHEN dari_tabungan=1 AND jenis_tabungan='diambil' THEN 'Tabungan (Diambil)'
                            ELSE kategori 
                        END as kategori_display,
                        CASE 
                            WHEN dari_tabungan=1 AND jenis_tabungan='ditabung' THEN 'pengeluaran'
                            WHEN dari_tabungan=1 AND jenis_tabungan='diambil' THEN 'pemasukan'
                            ELSE jenis 
                        END as jenis_display,
                        SUM(jumlah) as total 
                       FROM catatan_keuangan 
                       WHERE user_id = ? AND YEAR(tanggal) = ? AND MONTH(tanggal) = ?
                       GROUP BY kategori_display, jenis_display 
                       ORDER BY jenis_display, total DESC";
    $stmt = $conn->prepare($query_kategori);
    $stmt->bind_param("iss", $user_id, $tahun, $bulan_angka);
    $stmt->execute();
    $result_kategori = $stmt->get_result();
    $stmt->close();
}

// ===== PERSIAPAN DATA CHART DENGAN TERJEMAHAN =====
$kategori_chart = ['pemasukan' => [], 'pengeluaran' => []];
while ($row = $result_kategori->fetch_assoc()) {
    $kategori_display = $row['kategori_display'];
    
    if (strpos($kategori_display, 'Tabungan') !== false) {
        $kategori_display = translateCategory('Tabungan');
        if (strpos($row['kategori_display'], 'Ditabung') !== false) {
            $kategori_display .= ' (' . __('saved') . ')';
        } else if (strpos($row['kategori_display'], 'Diambil') !== false) {
            $kategori_display .= ' (' . __('withdrawn') . ')';
        }
    } else {
        $kategori_display = translateCategory($kategori_display);
    }
    
    if ($row['jenis_display'] == 'pemasukan') {
        $kategori_chart['pemasukan'][] = [
            'kategori_display' => $kategori_display,
            'total' => $row['total']
        ];
    } else {
        $kategori_chart['pengeluaran'][] = [
            'kategori_display' => $kategori_display,
            'total' => $row['total']
        ];
    }
}
$result_kategori->data_seek(0);

// ===== AMBIL TOTAL TARGET AKTIF =====
$query_target = "SELECT COUNT(*) as total FROM target_tabungan WHERE user_id = ? AND status = 'aktif'";
$stmt = $conn->prepare($query_target);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_target = $stmt->get_result();
$total_target_aktif = $result_target->fetch_assoc()['total'] ?? 0;
$stmt->close();
?>

<style>
/* ===== STYLE LAPORAN ===== */
.laporan-page .summary-card {
    padding: 14px 16px;
    border-radius: 12px;
    color: white;
    position: relative;
    overflow: hidden;
    min-height: 75px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    transition: all 0.3s ease;
}

.laporan-page .summary-card .card-icon {
    position: absolute;
    right: 12px;
    top: 12px;
    font-size: 1.6rem;
    opacity: 0.25;
}

.laporan-page .summary-card .card-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.9;
    font-weight: 600;
}

.laporan-page .summary-card .card-value {
    font-size: 1rem;
    font-weight: 700;
    margin-top: 2px;
}

.laporan-page .summary-card.bg-success {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

.laporan-page .summary-card.bg-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.laporan-page .summary-card.bg-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.laporan-page .summary-card.bg-info {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

/* ===== BADGE ===== */
.badge-tabungan-ditabung {
    background: #f59e0b;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.7rem;
}

.badge-tabungan-diambil {
    background: #3b82f6;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.7rem;
}

.badge-pemasukan {
    background: #22c55e;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.7rem;
}

.badge-pengeluaran {
    background: #ef4444;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.7rem;
}

/* ===== FILTER ===== */
.filter-form .form-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 4px;
}

.filter-form .form-control,
.filter-form .form-select {
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    padding: 8px 12px;
    font-size: 0.9rem;
    height: 44px;
}

.filter-form .form-control:focus,
.filter-form .form-select:focus {
    border-color: #4a6cf7;
    box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.1);
}

.filter-form .btn {
    border-radius: 10px;
    font-weight: 600;
    padding: 8px 16px;
    font-size: 0.85rem;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.filter-form .filter-type-btn {
    display: flex;
    gap: 4px;
}

.filter-form .filter-type-btn .btn {
    flex: 1;
    font-size: 0.75rem;
    padding: 6px 12px;
    height: 36px;
}

.filter-form .filter-type-btn .btn.active {
    background: #4a6cf7;
    color: white;
    border-color: #4a6cf7;
}

/* ===== RESPONSIVE FILTER ===== */
@media (min-width: 768px) {
    .filter-form .col-period {
        flex: 0 0 auto;
        width: 40%;
    }
    
    .filter-form .col-filter {
        flex: 0 0 auto;
        width: 15%;
    }
    
    .filter-form .col-cetak {
        flex: 0 0 auto;
        width: 15%;
    }
    
    .filter-form .col-type {
        flex: 0 0 auto;
        width: 30%;
    }
}

@media (max-width: 767.98px) {
    .filter-form .col-period {
        flex: 0 0 100%;
        width: 100%;
    }
    
    .filter-form .col-type {
        flex: 0 0 100%;
        width: 100%;
    }
    
    .filter-form .col-filter {
        flex: 0 0 calc(50% - 4px);
        width: calc(50% - 4px);
    }
    
    .filter-form .col-cetak {
        flex: 0 0 calc(50% - 4px);
        width: calc(50% - 4px);
    }
    
    .filter-form .form-control,
    .filter-form .form-select {
        font-size: 0.85rem;
        height: 40px;
        padding: 6px 10px;
    }
    
    .filter-form .btn {
        font-size: 0.8rem;
        height: 40px;
        padding: 6px 12px;
        width: 100%;
    }
    
    .filter-form .btn i {
        font-size: 0.9rem;
    }
    
    .filter-form .form-label {
        font-size: 0.75rem;
    }
    
    .filter-form .filter-type-btn .btn {
        font-size: 0.7rem;
        padding: 4px 8px;
        height: 32px;
    }
}

/* ===== TABEL ===== */
.table-transaksi {
    font-size: 0.85rem;
    margin-bottom: 0;
}

.table-transaksi thead th {
    background: #f8fafc;
    border-bottom: 2px solid #e5e7eb;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding: 10px 14px;
    color: #4b5563;
    white-space: nowrap;
}

.table-transaksi tbody td {
    padding: 10px 14px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
}

.table-transaksi tbody tr:hover {
    background: #f8fafc;
}

.table-transaksi tfoot td {
    padding: 10px 14px;
    border-top: 2px solid #e5e7eb;
    font-weight: 700;
    font-size: 0.85rem;
}

.table-transaksi tfoot .total-label {
    text-align: right;
    color: #4b5563;
}

.table-transaksi tfoot .total-value {
    text-align: right;
    white-space: nowrap;
}

.table-transaksi tfoot .total-value.text-success { color: #22c55e; }
.table-transaksi tfoot .total-value.text-danger { color: #ef4444; }
.table-transaksi tfoot .total-value.text-warning { color: #f59e0b; }
.table-transaksi tfoot .total-value.text-info { color: #3b82f6; }

@media (max-width: 767.98px) {
    .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .table-transaksi { font-size: 0.75rem; min-width: 600px; }
    .table-transaksi thead th { padding: 8px 10px; font-size: 0.65rem; }
    .table-transaksi tbody td { padding: 8px 10px; }
    .table-transaksi tfoot td { padding: 8px 10px; font-size: 0.75rem; }
}

/* ===== RINGKASAN ===== */
.ringkasan-tabungan {
    background: #fffbeb;
    border-radius: 12px;
}

/* ===== ANIMASI ===== */
.fade-in-up {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.5s ease;
}

.fade-in-up.show {
    opacity: 1;
    transform: translateY(0);
}

@media (max-width: 575.98px) {
    .laporan-page .summary-card {
        min-height: 60px;
        padding: 10px 12px;
    }
    .laporan-page .summary-card .card-value {
        font-size: 0.85rem;
    }
    .laporan-page .summary-card .card-icon {
        font-size: 1.2rem;
        right: 8px;
        top: 8px;
    }
    .laporan-page .summary-card .card-label {
        font-size: 0.55rem;
    }
}

@media print {
    .btn, .no-print, .navbar, .mobile-bottom-nav { display: none !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; }
    body { background: white !important; padding: 0 !important; }
    .container { max-width: 100% !important; padding: 0 !important; }
    .table-transaksi { font-size: 0.8rem; }
    .table-transaksi thead th { background: #f0f0f0 !important; }
}
</style>

<div class="laporan-page">

<!-- ===== FILTER ===== -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary">
                <i class="bi bi-file-earmark-text"></i> <?php echo __('report_title'); ?>
            </div>
            <div class="card-body">
                <form method="GET" class="filter-form">
                    <div class="row g-2 g-md-3 align-items-end">
                        <!-- Tipe Filter -->
                        <div class="col-type">
                            <label class="form-label"><?php echo __('filter_type'); ?></label>
                            <div class="filter-type-btn">
                                <button type="button" class="btn btn-outline-secondary <?php echo $filter_type == 'daily' ? 'active' : ''; ?>" 
                                        onclick="setFilter('daily')">
                                    <i class="bi bi-calendar-day"></i> <?php echo __('daily'); ?>
                                </button>
                                <button type="button" class="btn btn-outline-secondary <?php echo $filter_type == 'monthly' ? 'active' : ''; ?>" 
                                        onclick="setFilter('monthly')">
                                    <i class="bi bi-calendar-month"></i> <?php echo __('monthly'); ?>
                                </button>
                            </div>
                            <input type="hidden" name="filter_type" id="filter_type" value="<?php echo $filter_type; ?>">
                        </div>
                        
                        <!-- Periode -->
                        <div class="col-period">
                            <label class="form-label" id="periodLabel">
                                <?php echo $filter_type == 'daily' ? __('date') : __('report_month'); ?>
                            </label>
                            <?php if ($filter_type == 'daily'): ?>
                                <input type="date" name="tanggal" class="form-control" value="<?php echo $tanggal; ?>" id="periodInput">
                            <?php else: ?>
                                <input type="month" name="bulan" class="form-control" value="<?php echo $bulan; ?>" id="periodInput">
                            <?php endif; ?>
                        </div>
                        
                        <!-- Tombol Filter -->
                        <div class="col-filter">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter"></i> <?php echo __('filter'); ?>
                            </button>
                        </div>
                        
                        <!-- Tombol Cetak -->
                        <div class="col-cetak">
                            <button type="button" class="btn btn-success w-100" onclick="window.print()">
                                <i class="bi bi-printer"></i> <?php echo __('print'); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ===== SUMMARY CARDS ===== -->
<div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="summary-card bg-success fade-in-up show">
            <i class="bi bi-arrow-up-circle card-icon"></i>
            <div class="card-label"><?php echo __('total_income'); ?></div>
            <div class="card-value"><?php echo formatCurrency($total_pemasukan); ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-card bg-danger fade-in-up show" style="animation-delay: 0.1s;">
            <i class="bi bi-arrow-down-circle card-icon"></i>
            <div class="card-label"><?php echo __('total_expense'); ?></div>
            <div class="card-value"><?php echo formatCurrency($total_pengeluaran); ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-card bg-warning fade-in-up show" style="animation-delay: 0.15s;">
            <i class="bi bi-piggy-bank-fill card-icon"></i>
            <div class="card-label"><?php echo __('total_savings'); ?></div>
            <div class="card-value"><?php echo formatCurrency($total_tabungan_bersih); ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-card <?php echo $saldo >= 0 ? 'bg-info' : 'bg-danger'; ?> fade-in-up show" style="animation-delay: 0.2s;">
            <i class="bi bi-wallet card-icon"></i>
            <div class="card-label"><?php echo __('balance'); ?></div>
            <div class="card-value"><?php echo formatCurrency($saldo); ?></div>
        </div>
    </div>
</div>

<!-- ===== RINGKASAN ===== -->
<?php if ($total_target_aktif > 0 || $total_tabungan_bersih > 0): ?>
<div class="row g-2 g-md-3 mb-3">
    <div class="col-12">
        <div class="card border-warning ringkasan-tabungan">
            <div class="card-body p-2 p-md-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h6 class="mb-1"><i class="bi bi-piggy-bank-fill text-warning"></i> <?php echo __('report_savings_summary'); ?></h6>
                        <div class="d-flex flex-wrap gap-2 gap-md-3">
                            <span><span class="label"><?php echo __('active_targets'); ?>:</span> <span class="value"><?php echo $total_target_aktif; ?></span></span>
                            <span><span class="label"><?php echo __('report_total_saved'); ?>:</span> <span class="value"><?php echo formatCurrency($total_ditabung); ?></span></span>
                            <span><span class="label"><?php echo __('report_total_withdrawn'); ?>:</span> <span class="value"><?php echo formatCurrency($total_diambil); ?></span></span>
                            <span><span class="label text-success"><?php echo __('balance'); ?>:</span> <span class="value text-success"><?php echo formatCurrency($total_tabungan_bersih); ?></span></span>
                        </div>
                    </div>
                    <a href="target.php" class="btn btn-sm btn-warning">
                        <i class="bi bi-arrow-right"></i> <?php echo __('manage'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== INFO PERIODE ===== -->
<div class="row g-2 g-md-3 mb-3">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 
            <?php echo __('showing_transactions_for'); ?>: <strong><?php echo $label_filter; ?></strong>
            <?php if ($filter_type == 'daily'): ?>
                <span class="badge bg-primary ms-2"><?php echo __('daily'); ?></span>
            <?php else: ?>
                <span class="badge bg-primary ms-2"><?php echo __('monthly'); ?></span>
            <?php endif; ?>
            <span class="badge bg-secondary ms-2"><?php echo $result->num_rows; ?> <?php echo __('transactions'); ?></span>
        </div>
    </div>
</div>

<!-- ===== DETAIL TRANSAKSI ===== -->
<div class="card">
    <div class="card-header bg-primary d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul"></i> <?php echo __('report_detail'); ?></span>
        <span class="badge bg-light text-dark"><?php echo $result->num_rows; ?> <?php echo __('transactions'); ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-transaksi">
                <thead>
                    <tr>
                        <th><?php echo __('date'); ?></th>
                        <th><?php echo __('type'); ?></th>
                        <th><?php echo __('category'); ?></th>
                        <th><?php echo __('description'); ?></th>
                        <th class="text-end"><?php echo __('amount'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $badge_class = 'badge-pengeluaran';
                            $jenis_text = $row['jenis_display'];
                            $kategori_display = $row['kategori_display'];
                            
                            if ($row['dari_tabungan'] == 1) {
                                if ($row['jenis_tabungan'] == 'ditabung') {
                                    $badge_class = 'badge-tabungan-ditabung';
                                    $jenis_text = __('saved');
                                    $kategori_display = translateCategory('Tabungan') . ' (' . __('saved') . ')';
                                } else {
                                    $badge_class = 'badge-tabungan-diambil';
                                    $jenis_text = __('withdrawn');
                                    $kategori_display = translateCategory('Tabungan') . ' (' . __('withdrawn') . ')';
                                }
                            } else {
                                if ($row['jenis'] == 'pemasukan') {
                                    $badge_class = 'badge-pemasukan';
                                    $jenis_text = __('income');
                                } else {
                                    $jenis_text = __('expense');
                                }
                                $kategori_display = translateCategory($row['kategori']);
                            }
                            
                            $is_positive = ($row['jenis'] == 'pemasukan' || ($row['dari_tabungan'] == 1 && $row['jenis_tabungan'] == 'diambil'));
                            $amount_class = $is_positive ? 'positive' : 'negative';
                            $amount_sign = $is_positive ? '+' : '-';
                        ?>
                            <tr>
                                <td class="tx-tanggal"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                <td>
                                    <span class="<?php echo $badge_class; ?>"><?php echo $jenis_text; ?></span>
                                </td>
                                <td class="tx-kategori"><?php echo htmlspecialchars($kategori_display); ?></td>
                                <td class="tx-deskripsi" title="<?php echo htmlspecialchars($row['deskripsi']); ?>">
                                    <?php echo htmlspecialchars($row['deskripsi']) ?: '-'; ?>
                                </td>
                                <td class="tx-jumlah <?php echo $amount_class; ?> text-end">
                                    <?php echo $amount_sign . ' ' . formatCurrency($row['jumlah']); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="bi bi-inbox fs-2 d-block text-muted"></i>
                                <span class="text-muted"><?php echo __('no_transactions'); ?></span>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="total-label"><?php echo __('report_total_income'); ?></td>
                        <td class="total-value text-success">+ <?php echo formatCurrency($total_pemasukan); ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="total-label"><?php echo __('report_total_expense'); ?></td>
                        <td class="total-value text-danger">- <?php echo formatCurrency($total_pengeluaran); ?></td>
                    </tr>
                    <?php if ($total_ditabung > 0): ?>
                    <tr>
                        <td colspan="4" class="total-label"><?php echo __('report_total_saved'); ?></td>
                        <td class="total-value text-warning">- <?php echo formatCurrency($total_ditabung); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($total_diambil > 0): ?>
                    <tr>
                        <td colspan="4" class="total-label"><?php echo __('report_total_withdrawn'); ?></td>
                        <td class="total-value text-info">+ <?php echo formatCurrency($total_diambil); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr style="border-top: 3px solid #4a6cf7; background: #f0f4ff;">
                        <td colspan="4" class="total-label" style="font-size: 0.95rem;"><?php echo __('balance'); ?></td>
                        <td class="total-value <?php echo $saldo >= 0 ? 'text-success' : 'text-danger'; ?>" style="font-size: 1rem;">
                            <?php echo $saldo >= 0 ? '+' : ''; ?> <?php echo formatCurrency($saldo); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- ===== KETERANGAN ===== -->
<div class="card mt-3">
    <div class="card-body p-2 p-md-3">
        <div class="d-flex flex-wrap gap-2 gap-md-3">
            <span><span class="badge badge-pemasukan"><?php echo __('income'); ?></span> <?php echo __('legend_income'); ?></span>
            <span><span class="badge badge-pengeluaran"><?php echo __('expense'); ?></span> <?php echo __('legend_expense'); ?></span>
            <span><span class="badge badge-tabungan-ditabung"><?php echo __('saved'); ?></span> <?php echo __('legend_saved'); ?></span>
            <span><span class="badge badge-tabungan-diambil"><?php echo __('withdrawn'); ?></span> <?php echo __('legend_withdrawn'); ?></span>
        </div>
    </div>
</div>

</div>

<script>
function setFilter(type) {
    document.getElementById('filter_type').value = type;
    const periodLabel = document.getElementById('periodLabel');
    const periodInput = document.getElementById('periodInput');
    
    if (type === 'daily') {
        periodLabel.textContent = '<?php echo __('date'); ?>';
        periodInput.type = 'date';
        periodInput.name = 'tanggal';
        periodInput.value = '<?php echo date('Y-m-d'); ?>';
    } else {
        periodLabel.textContent = '<?php echo __('report_month'); ?>';
        periodInput.type = 'month';
        periodInput.name = 'bulan';
        periodInput.value = '<?php echo date('Y-m'); ?>';
    }
    
    // Update button active state
    document.querySelectorAll('.filter-type-btn .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>