<?php
// includes/currency_api.php

class CurrencyAPI {
    private $cache_file;
    private $cache_time = 3600; // 1 jam cache (3600 detik)
    private $api_url = "https://open.er-api.com/v6/latest/";
    
    public function __construct() {
        $this->cache_file = __DIR__ . '/../cache/currency_rates.json';
        // Buat folder cache jika belum ada
        if (!file_exists(__DIR__ . '/../cache')) {
            mkdir(__DIR__ . '/../cache', 0777, true);
        }
    }
    
    /**
     * Mengambil kurs dari API dengan caching
     * @param string $base Mata uang dasar (default: USD)
     * @return array Data kurs
     */
    public function getRates($base = 'USD') {
        // Cek cache
        $cached = $this->getCache($base);
        if ($cached) {
            return $cached;
        }
        
        // URL API (tanpa API key)
        $url = $this->api_url . $base;
        
        // Gunakan cURL untuk mengambil data
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Monexa Currency Converter/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['rates']) && isset($data['base_code'])) {
                // Simpan cache
                $this->saveCache($base, $data['rates'], $data['time_last_update_unix'] ?? time());
                return $data['rates'];
            }
        }
        
        // Log error jika perlu
        error_log("Currency API Error: HTTP $httpCode - $curlError");
        
        // Fallback ke data default jika API gagal
        return $this->getDefaultRates($base);
    }
    
    /**
     * Konversi mata uang
     * @param float $amount Jumlah yang akan dikonversi
     * @param string $from Mata uang asal
     * @param string $to Mata uang tujuan
     * @return float Hasil konversi
     */
    public function convert($amount, $from, $to) {
        if ($from === $to) {
            return $amount;
        }
        
        $rates = $this->getRates($from);
        if (isset($rates[$to])) {
            return $amount * $rates[$to];
        }
        
        // Jika mata uang tidak ditemukan, coba dengan USD sebagai base
        $ratesUSD = $this->getRates('USD');
        if (isset($ratesUSD[$from]) && isset($ratesUSD[$to])) {
            // Konversi via USD
            $amountInUSD = $amount / $ratesUSD[$from];
            return $amountInUSD * $ratesUSD[$to];
        }
        
        return $amount;
    }
    
    /**
     * Mendapatkan informasi update terakhir
     */
    public function getLastUpdate($base = 'USD') {
        if (file_exists($this->cache_file)) {
            $data = json_decode(file_get_contents($this->cache_file), true);
            if ($data && isset($data['base']) && $data['base'] == $base) {
                return $data['last_update'] ?? 0;
            }
        }
        return 0;
    }
    
    /**
     * Format waktu update
     */
    public function getLastUpdateFormatted($base = 'USD') {
        $timestamp = $this->getLastUpdate($base);
        if ($timestamp) {
            return date('d M Y H:i:s', $timestamp);
        }
        return 'N/A';
    }
    
    /**
     * Ambil cache jika masih valid
     */
    private function getCache($base) {
        if (file_exists($this->cache_file)) {
            $data = json_decode(file_get_contents($this->cache_file), true);
            if ($data && isset($data['base']) && $data['base'] == $base) {
                if (time() - $data['timestamp'] < $this->cache_time) {
                    return $data['rates'];
                }
            }
        }
        return false;
    }
    
    /**
     * Simpan cache
     */
    private function saveCache($base, $rates, $lastUpdate) {
        $data = [
            'base' => $base,
            'rates' => $rates,
            'timestamp' => time(),
            'last_update' => $lastUpdate
        ];
        file_put_contents($this->cache_file, json_encode($data));
    }
    
    /**
     * Data default jika API offline (fallback)
     */
    private function getDefaultRates($base) {
        $defaultRates = [
            'USD' => 1,
            'EUR' => 0.92,
            'GBP' => 0.79,
            'IDR' => 15650,
            'VND' => 25450,
            'JPY' => 148.5,
            'KRW' => 1320,
            'CNY' => 7.23,
            'SGD' => 1.35,
            'MYR' => 4.72,
            'THB' => 35.8,
            'PHP' => 56.2,
            'AUD' => 1.53,
            'NZD' => 1.64,
            'CAD' => 1.36,
            'CHF' => 0.88,
            'INR' => 83.2,
            'SAR' => 3.75,
            'AED' => 3.67,
            'TRY' => 30.5,
            'RUB' => 90.5,
            'BRL' => 4.95,
            'ZAR' => 18.8,
            'MXN' => 17.2,
            'EGP' => 47.5,
            'PKR' => 278,
            'BDT' => 109,
            'LKR' => 310,
            'NPR' => 133,
            'NGN' => 1580,
            'KES' => 155,
            'GHS' => 12.3,
            'TND' => 3.12,
            'DZD' => 134,
            'MAD' => 10.1,
            'QAR' => 3.64,
            'KWD' => 0.307,
            'BHD' => 0.376,
            'OMR' => 0.385,
            'JOD' => 0.709,
        ];
        
        // Konversi ke base currency yang diminta
        if ($base !== 'USD' && isset($defaultRates[$base])) {
            $baseRate = $defaultRates[$base];
            $converted = [];
            foreach ($defaultRates as $currency => $rate) {
                $converted[$currency] = $rate / $baseRate;
            }
            return $converted;
        }
        
        return $defaultRates;
    }
    
    /**
     * Clear cache (force refresh)
     */
    public function clearCache() {
        if (file_exists($this->cache_file)) {
            unlink($this->cache_file);
        }
    }
}

// ===== FUNGSI HELPER =====

/**
 * Mendapatkan kurs real-time
 */
function getLiveRates($base = 'USD') {
    $api = new CurrencyAPI();
    return $api->getRates($base);
}

/**
 * Konversi mata uang
 */
function convertCurrency($amount, $from, $to) {
    $api = new CurrencyAPI();
    return $api->convert($amount, $from, $to);
}

/**
 * Mendapatkan waktu update terakhir
 */
function getLastUpdateTime($base = 'USD') {
    $api = new CurrencyAPI();
    return $api->getLastUpdateFormatted($base);
}

/**
 * Clear cache
 */
function clearCurrencyCache() {
    $api = new CurrencyAPI();
    $api->clearCache();
}
?>