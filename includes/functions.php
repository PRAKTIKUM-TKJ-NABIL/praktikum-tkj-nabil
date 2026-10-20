<?php
// includes/functions.php

// ===== FUNGSI UNTUK MENERJEMAHKAN =====
function __($key) {
    global $lang_data;
    
    if (!isset($lang_data) || !is_array($lang_data)) {
        $lang = isset($_SESSION['language']) ? $_SESSION['language'] : 'en';
        $lang_data = loadLanguage($lang);
    }
    
    return isset($lang_data[$key]) ? $lang_data[$key] : $key;
}

// ===== FUNGSI UNTUK FORMAT MATA UANG =====
function formatCurrency($amount, $lang = null) {
    if ($lang === null) {
        $lang = isset($_SESSION['language']) ? $_SESSION['language'] : 'en';
    }
    
    $symbol = getCurrencySymbol($lang);
    $code = getCurrencyCode($lang);
    
    switch($code) {
        case 'IDR':
        case 'VND':
            return $symbol . ' ' . number_format($amount, 0, ',', '.');
        case 'JPY':
            return $symbol . number_format($amount, 0);
        case 'GBP':
        default:
            return $symbol . number_format($amount, 2, '.', ',');
    }
}

// ===== FUNGSI UNTUK FORMAT RUPIAH (Legacy - gunakan formatCurrency) =====
function formatRupiah($number) {
    return formatCurrency($number);
}

// ===== FUNGSI UNTUK FORMAT TANGGAL =====
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

// ===== FUNGSI UNTUK MENERJEMAHKAN KATEGORI =====
function translateCategory($category, $lang = null) {
    if ($lang === null) {
        $lang = isset($_SESSION['language']) ? $_SESSION['language'] : 'en';
    }
    
    // Mapping kategori ke berbagai bahasa
    $categories = [
        'Gaji' => ['en' => 'Salary', 'id' => 'Gaji', 'vi' => 'Lương', 'ja' => '給料'],
        'Bonus' => ['en' => 'Bonus', 'id' => 'Bonus', 'vi' => 'Thưởng', 'ja' => 'ボーナス'],
        'Investasi' => ['en' => 'Investment', 'id' => 'Investasi', 'vi' => 'Đầu tư', 'ja' => '投資'],
        'Tabungan' => ['en' => 'Savings', 'id' => 'Tabungan', 'vi' => 'Tiết kiệm', 'ja' => '貯蓄'],
        'Makanan' => ['en' => 'Food', 'id' => 'Makanan', 'vi' => 'Thực phẩm', 'ja' => '食料品'],
        'Transportasi' => ['en' => 'Transportation', 'id' => 'Transportasi', 'vi' => 'Vận chuyển', 'ja' => '交通'],
        'Belanja' => ['en' => 'Shopping', 'id' => 'Belanja', 'vi' => 'Mua sắm', 'ja' => 'ショッピング'],
        'Tagihan' => ['en' => 'Bills', 'id' => 'Tagihan', 'vi' => 'Hóa đơn', 'ja' => '請求書'],
        'Hiburan' => ['en' => 'Entertainment', 'id' => 'Hiburan', 'vi' => 'Giải trí', 'ja' => 'エンターテイメント'],
        'Kesehatan' => ['en' => 'Health', 'id' => 'Kesehatan', 'vi' => 'Sức khỏe', 'ja' => '健康'],
    ];
    
    if (isset($categories[$category]) && isset($categories[$category][$lang])) {
        return $categories[$category][$lang];
    }
    
    return $category;
}

// ===== FUNGSI UNTUK MENERJEMAHKAN JENIS TRANSAKSI =====
function translateTransactionType($type, $lang = null) {
    if ($lang === null) {
        $lang = isset($_SESSION['language']) ? $_SESSION['language'] : 'en';
    }
    
    $types = [
        'pemasukan' => ['en' => 'Income', 'id' => 'Pemasukan', 'vi' => 'Thu nhập', 'ja' => '収入'],
        'pengeluaran' => ['en' => 'Expense', 'id' => 'Pengeluaran', 'vi' => 'Chi tiêu', 'ja' => '支出'],
        'ditabung' => ['en' => 'Saved', 'id' => 'Ditabung', 'vi' => 'Đã tiết kiệm', 'ja' => '貯蓄済み'],
        'diambil' => ['en' => 'Withdrawn', 'id' => 'Diambil', 'vi' => 'Đã rút', 'ja' => '引き出し済み'],
    ];
    
    if (isset($types[$type]) && isset($types[$type][$lang])) {
        return $types[$type][$lang];
    }
    
    return $type;
}
?>