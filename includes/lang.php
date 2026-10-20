<?php
// includes/lang.php

function loadLanguage($lang = 'en') {
    $available_langs = ['en', 'id', 'vi', 'ja'];
    
    if (!in_array($lang, $available_langs)) {
        $lang = 'en';
    }
    
    $lang_file = __DIR__ . "/../lang/{$lang}.php";
    
    if (file_exists($lang_file)) {
        return include $lang_file;
    }
    
    return include __DIR__ . "/../lang/en.php";
}

function getAvailableLanguages() {
    return [
        'en' => 'English',
        'id' => 'Indonesian',
        'vi' => 'Tiếng Việt',
        'ja' => '日本語'
    ];
}

function getLanguageName($code) {
    $languages = getAvailableLanguages();
    return isset($languages[$code]) ? $languages[$code] : $code;
}

// ===== FUNGSI MATA UANG =====
// HANYA deklarasi fungsi getCurrencySymbol dan getCurrencyCode di sini
// formatCurrency() sudah ada di functions.php

function getCurrencySymbol($lang = null) {
    if ($lang === null) {
        $lang = isset($_SESSION['language']) ? $_SESSION['language'] : 'en';
    }
    
    $currencies = [
        'en' => '£',   // GBP
        'id' => 'Rp',  // IDR
        'vi' => '₫',   // VND
        'ja' => '¥'    // JPY
    ];
    
    return isset($currencies[$lang]) ? $currencies[$lang] : 'Rp';
}

function getCurrencyCode($lang = null) {
    if ($lang === null) {
        $lang = isset($_SESSION['language']) ? $_SESSION['language'] : 'en';
    }
    
    $codes = [
        'en' => 'GBP',
        'id' => 'IDR',
        'vi' => 'VND',
        'ja' => 'JPY'
    ];
    
    return isset($codes[$lang]) ? $codes[$lang] : 'IDR';
}
?>