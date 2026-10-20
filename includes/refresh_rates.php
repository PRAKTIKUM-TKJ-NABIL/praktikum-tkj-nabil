<?php
// includes/refresh_rates.php
// Endpoint untuk refresh kurs dari API

require_once 'currency_api.php';

header('Content-Type: application/json');

$api = new CurrencyAPI();
$api->clearCache(); // Hapus cache

// Ambil data baru
$rates = $api->getRates('USD');
$lastUpdate = $api->getLastUpdateFormatted('USD');

if ($rates && is_array($rates)) {
    echo json_encode([
        'success' => true,
        'rates' => $rates,
        'last_update' => $lastUpdate
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch exchange rates'
    ]);
}
?>