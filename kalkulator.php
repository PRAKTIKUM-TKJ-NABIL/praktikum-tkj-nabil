<?php
require_once __DIR__ . '/config/database.php';
require_once 'includes/currency_api.php';

// ===== LOAD LANGUAGE =====
require_once 'includes/lang.php';
require_once 'includes/functions.php';

// Load language
$lang = $_SESSION['language'] ?? 'en';
$lang_data = loadLanguage($lang);

if (!isset($conn) || $conn->connect_error) {
    die("Koneksi database gagal");
}

include 'includes/header.php';

// ===== AMBIL KURS REAL-TIME DARI API =====
$currencyAPI = new CurrencyAPI();
$rates = $currencyAPI->getRates('USD');
$lastUpdate = $currencyAPI->getLastUpdateFormatted('USD');

// Daftar mata uang yang didukung API
$supportedCurrencies = array_keys($rates);
sort($supportedCurrencies);

// Fungsi helper untuk flag dan nama mata uang
function getFlagClass($code) {
    $flags = [
        'USD' => 'us', 'EUR' => 'eu', 'GBP' => 'gb', 'IDR' => 'id',
        'VND' => 'vn', 'JPY' => 'jp', 'KRW' => 'kr', 'CNY' => 'cn',
        'SGD' => 'sg', 'MYR' => 'my', 'THB' => 'th', 'PHP' => 'ph',
        'AUD' => 'au', 'NZD' => 'nz', 'CAD' => 'ca', 'CHF' => 'ch',
        'INR' => 'in', 'SAR' => 'sa', 'AED' => 'ae', 'TRY' => 'tr',
        'RUB' => 'ru', 'BRL' => 'br', 'ZAR' => 'za', 'MXN' => 'mx',
        'EGP' => 'eg', 'PKR' => 'pk', 'BDT' => 'bd', 'LKR' => 'lk',
        'NPR' => 'np', 'NGN' => 'ng', 'KES' => 'ke', 'GHS' => 'gh',
        'TND' => 'tn', 'DZD' => 'dz', 'MAD' => 'ma', 'QAR' => 'qa',
        'KWD' => 'kw', 'BHD' => 'bh', 'OMR' => 'om', 'JOD' => 'jo'
    ];
    return $flags[$code] ?? 'us';
}

function getCurrencyName($code) {
    $names = [
        'USD' => 'US Dollar', 'EUR' => 'Euro', 'GBP' => 'British Pound',
        'IDR' => 'Indonesian Rupiah', 'VND' => 'Vietnamese Dong',
        'JPY' => 'Japanese Yen', 'KRW' => 'South Korean Won',
        'CNY' => 'Chinese Yuan', 'SGD' => 'Singapore Dollar',
        'MYR' => 'Malaysian Ringgit', 'THB' => 'Thai Baht',
        'PHP' => 'Philippine Peso', 'AUD' => 'Australian Dollar',
        'NZD' => 'New Zealand Dollar', 'CAD' => 'Canadian Dollar',
        'CHF' => 'Swiss Franc', 'INR' => 'Indian Rupee',
        'SAR' => 'Saudi Riyal', 'AED' => 'UAE Dirham',
        'TRY' => 'Turkish Lira', 'RUB' => 'Russian Ruble',
        'BRL' => 'Brazilian Real', 'ZAR' => 'South African Rand',
        'MXN' => 'Mexican Peso', 'EGP' => 'Egyptian Pound',
        'PKR' => 'Pakistani Rupee', 'BDT' => 'Bangladeshi Taka',
        'LKR' => 'Sri Lankan Rupee', 'NPR' => 'Nepalese Rupee',
        'NGN' => 'Nigerian Naira', 'KES' => 'Kenyan Shilling',
        'GHS' => 'Ghanaian Cedi', 'TND' => 'Tunisian Dinar',
        'DZD' => 'Algerian Dinar', 'MAD' => 'Moroccan Dirham',
        'QAR' => 'Qatari Riyal', 'KWD' => 'Kuwaiti Dinar',
        'BHD' => 'Bahraini Dinar', 'OMR' => 'Omani Rial',
        'JOD' => 'Jordanian Dinar'
    ];
    return $names[$code] ?? $code;
}
?>

<style>
/* ===== FLAG ICON STYLE ===== */
.flag-icon-option {
    display: inline-block;
    width: 20px;
    height: 15px;
    margin-right: 8px;
    vertical-align: middle;
    border-radius: 2px;
    box-shadow: 0 0 2px rgba(0,0,0,0.2);
}

.flag-icon-display {
    font-size: 2.5rem;
    width: 50px;
    text-align: center;
    flex-shrink: 0;
    line-height: 1;
}

.flag-icon-display .flag-icon {
    width: 50px;
    height: 38px;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* ===== KALKULATOR STYLES ===== */
.calculator-display {
    background: #1a1a2e;
    color: white;
    border-radius: 12px;
    padding: 20px;
    text-align: right;
    min-height: 80px;
    font-family: 'Courier New', monospace;
}

.calculator-display .display {
    font-size: 1.8rem;
    font-weight: 700;
    overflow: hidden;
    text-overflow: ellipsis;
    word-wrap: break-word;
}

.calculator-display .history {
    font-size: 0.8rem;
    opacity: 0.6;
    min-height: 20px;
    color: #9ca3af;
}

.calc-btn {
    width: 100%;
    padding: 14px 0;
    font-size: 1.1rem;
    font-weight: 700;
    border: none;
    border-radius: 10px;
    transition: all 0.15s ease;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
}

.calc-btn:active {
    transform: scale(0.92);
}

.calc-btn.number {
    background: #f3f4f6;
    color: #1a1a2e;
}

.calc-btn.number:hover {
    background: #e5e7eb;
}

.calc-btn.operator {
    background: #4a6cf7;
    color: white;
}

.calc-btn.operator:hover {
    background: #3a5de7;
}

.calc-btn.equals {
    background: #22c55e;
    color: white;
}

.calc-btn.equals:hover {
    background: #16a34a;
}

.calc-btn.clear {
    background: #ef4444;
    color: white;
}

.calc-btn.clear:hover {
    background: #dc2626;
}

.calc-btn.function {
    background: #6b7280;
    color: white;
}

.calc-btn.function:hover {
    background: #4b5563;
}

.history-list {
    max-height: 200px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.history-list::-webkit-scrollbar {
    width: 4px;
}

.history-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.history-list::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 10px;
}

.history-list::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

/* ===== TABS ===== */
.nav-tabs-custom {
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 20px;
}

.nav-tabs-custom .nav-link {
    border: none;
    padding: 10px 24px;
    font-weight: 600;
    font-size: 0.9rem;
    color: #6b7280;
    border-radius: 10px 10px 0 0;
    transition: all 0.2s ease;
}

.nav-tabs-custom .nav-link:hover {
    color: #4a6cf7;
    background: #f0f4ff;
}

.nav-tabs-custom .nav-link.active {
    color: #4a6cf7;
    background: white;
    border-bottom: 3px solid #4a6cf7;
}

.nav-tabs-custom .nav-link i {
    margin-right: 8px;
}

/* ===== CURRENCY CONVERTER ===== */
.currency-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #e5e7eb;
}

.currency-card .currency-row {
    display: flex;
    gap: 12px;
    align-items: center;
}

.currency-card .currency-row .flag-wrapper {
    flex-shrink: 0;
    width: 50px;
    text-align: center;
}

.currency-card .currency-row .flag-wrapper .flag-icon {
    width: 45px;
    height: 34px;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}

.currency-card .currency-row .flex-grow {
    flex: 1;
}

.currency-card .currency-row .amount-box {
    width: 120px;
    flex-shrink: 0;
}

.currency-card .form-select {
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    padding: 8px 12px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 12px 12px;
    appearance: none;
    -webkit-appearance: none;
}

.currency-card .form-select:focus {
    border-color: #4a6cf7;
    box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.1);
}

.currency-card .form-select option {
    padding: 6px 10px;
}

.currency-card .rate-info {
    background: #f0f4ff;
    border-radius: 10px;
    padding: 12px 16px;
    text-align: center;
    font-weight: 600;
    color: #4a6cf7;
}

.currency-card .swap-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 2px solid #e5e7eb;
    background: white;
    color: #4a6cf7;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    cursor: pointer;
    margin: 0 auto;
}

.currency-card .swap-btn:hover {
    background: #4a6cf7;
    color: white;
    border-color: #4a6cf7;
}

.currency-card .btn-convert {
    background: linear-gradient(135deg, #4a6cf7 0%, #6a3de8 100%);
    color: white;
    border: none;
    padding: 12px;
    border-radius: 10px;
    font-weight: 600;
    width: 100%;
    transition: all 0.2s ease;
}

.currency-card .btn-convert:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(74, 108, 247, 0.35);
}

.rate-update-info {
    font-size: 0.7rem;
    color: #6b7280;
    text-align: center;
    margin-top: 6px;
}

.rate-update-info .live-badge {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: #22c55e;
    border-radius: 50%;
    margin-right: 6px;
    animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
}

/* ===== CUSTOM SELECT OPTION WITH FLAG ===== */
.currency-option {
    display: flex;
    align-items: center;
    gap: 8px;
}

.currency-option .flag-icon {
    width: 20px;
    height: 15px;
    border-radius: 2px;
    box-shadow: 0 0 2px rgba(0,0,0,0.15);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 575.98px) {
    .calculator-display .display {
        font-size: 1.5rem;
    }
    
    .calc-btn {
        font-size: 0.95rem;
        padding: 12px 0;
    }
    
    .calculator-display {
        padding: 14px;
        min-height: 65px;
    }
    
    .nav-tabs-custom .nav-link {
        padding: 8px 12px;
        font-size: 0.8rem;
    }
    
    .nav-tabs-custom .nav-link i {
        margin-right: 4px;
    }
    
    .currency-card .currency-row {
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .currency-card .currency-row .flag-wrapper {
        width: 40px;
    }
    
    .currency-card .currency-row .flag-wrapper .flag-icon {
        width: 36px;
        height: 27px;
    }
    
    .currency-card .currency-row .amount-box {
        width: 100%;
    }
    
    .currency-card .swap-btn {
        width: 38px;
        height: 38px;
        font-size: 1rem;
        margin: 4px auto;
    }
    
    .currency-card .currency-row .flex-grow {
        flex: 1 1 100%;
    }
}

@media (min-width: 576px) and (max-width: 767px) {
    .currency-card .currency-row .amount-box {
        width: 100px;
    }
}
</style>

<div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8">
        <div class="card shadow-lg fade-in-up">
            <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-calculator"></i> <?php echo __('calc_title'); ?></h5>
                <span class="badge bg-light text-dark" style="font-size:0.65rem;">
                    <i class="bi bi-wifi"></i> Live
                </span>
            </div>
            <div class="card-body p-3 p-md-4">
                
                <!-- ===== TABS ===== -->
                <ul class="nav nav-tabs-custom nav-tabs" id="calcTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="standard-tab" data-bs-toggle="tab" data-bs-target="#standard" type="button" role="tab">
                            <i class="bi bi-calculator"></i> <?php echo __('calc_standard'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="currency-tab" data-bs-toggle="tab" data-bs-target="#currency" type="button" role="tab">
                            <i class="bi bi-currency-exchange"></i> <?php echo __('calc_currency'); ?>
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    
                    <!-- ============================================================ -->
                    <!-- TAB 1: KALKULATOR STANDARD -->
                    <!-- ============================================================ -->
                    <div class="tab-pane fade show active" id="standard" role="tabpanel">
                        <div class="calculator-display mb-3">
                            <div id="history" class="history"></div>
                            <div id="display" class="display">0</div>
                        </div>
                        
                        <div class="row g-2">
                            <div class="col-3">
                                <button class="calc-btn clear" onclick="clearDisplay()">C</button>
                            </div>
                            <div class="col-3">
                                <button class="calc-btn function" onclick="backspace()">⌫</button>
                            </div>
                            <div class="col-3">
                                <button class="calc-btn function" onclick="appendOperator('%')">%</button>
                            </div>
                            <div class="col-3">
                                <button class="calc-btn operator" onclick="appendOperator('/')">÷</button>
                            </div>
                            
                            <div class="col-3">
                                <button class="calc-btn number" onclick="appendNumber('7')">7</button>
                            </div>
                            <div class="col-3">
                                <button class="calc-btn number" onclick="appendNumber('8')">8</button>
                            </div>
                            <div class="col-3">
                                <button class="calc-btn number" onclick="appendNumber('9')">9</button>
                            </div>
                            <div class="col-3">
                                <button class="calc-btn operator" onclick="appendOperator('*')">×</button>
                            </div>
                            
                            <div class="col-3">
                                <button class="calc-btn number" onclick="appendNumber('4')">4</button>
                            </div>
                            <div class="col-3">
                                <button class="calc-btn number" onclick="appendNumber('5')">5</button>
                            </div>
                            <div class="col-3">
                                <button class="calc-btn number" onclick="appendNumber('6')">6</button>
                            </div>
                            <div class="col-3">
                                <button class="calc-btn operator" onclick="appendOperator('-')">−</button>
                            </div>
                            
                            <div class="col-3">
                                <button class="calc-btn number" onclick="appendNumber('1')">1</button>
                            </div>
                            <div class="col-3">
                                <button class="calc-btn number" onclick="appendNumber('2')">2</button>
                            </div>
                            <div class="col-3">
                                <button class="calc-btn number" onclick="appendNumber('3')">3</button>
                            </div>
                            <div class="col-3">
                                <button class="calc-btn operator" onclick="appendOperator('+')">+</button>
                            </div>
                            
                            <div class="col-6">
                                <button class="calc-btn number" onclick="appendNumber('0')">0</button>
                            </div>
                            <div class="col-3">
                                <button class="calc-btn number" onclick="appendDecimal()">.</button>
                            </div>
                            <div class="col-3">
                                <button class="calc-btn equals" onclick="calculate()">=</button>
                            </div>
                        </div>
                        
                        <div class="row g-2 mt-2">
                            <div class="col-4">
                                <button class="calc-btn function w-100" onclick="squareRoot()">√</button>
                            </div>
                            <div class="col-4">
                                <button class="calc-btn function w-100" onclick="square()">x²</button>
                            </div>
                            <div class="col-4">
                                <button class="calc-btn function w-100" onclick="toggleSign()">±</button>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="card bg-light">
                                <div class="card-header bg-secondary text-white py-2">
                                    <h6 class="mb-0"><i class="bi bi-clock-history"></i> <?php echo __('calc_history'); ?></h6>
                                </div>
                                <div class="card-body p-2">
                                    <div class="history-list" id="historyList">
                                        <p class="text-muted text-center small mb-0"><?php echo __('calc_no_history'); ?></p>
                                    </div>
                                </div>
                                <div class="card-footer p-1">
                                    <button class="btn btn-sm btn-danger w-100" onclick="clearHistory()">
                                        <i class="bi bi-trash"></i> <?php echo __('calc_clear'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ============================================================ -->
                    <!-- TAB 2: CURRENCY CONVERTER -->
                    <!-- ============================================================ -->
                    <div class="tab-pane fade" id="currency" role="tabpanel">
                        <div class="currency-card">
                            <div class="rate-update-info mb-2">
                                <span class="live-badge"></span>
                                <?php echo __('calc_rates_updated'); ?>: <strong><?php echo $lastUpdate; ?></strong>
                                <span class="ms-2 text-muted">(<?php echo __('calc_rates_source'); ?>)</span>
                            </div>
                            
                            <div class="currency-row mb-3">
                                <div class="flag-wrapper" id="fromFlagWrapper">
                                    <span class="flag-icon flag-icon-us flag-icon-display"></span>
                                </div>
                                <div class="flex-grow">
                                    <label class="form-label"><?php echo __('calc_from'); ?></label>
                                    <select class="form-select" id="fromCurrency">
                                        <?php foreach ($supportedCurrencies as $code): ?>
                                            <option value="<?php echo $code; ?>" <?php echo $code === 'USD' ? 'selected' : ''; ?>>
                                                <?php echo getFlagClass($code); ?> <?php echo $code; ?> - <?php echo getCurrencyName($code); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="amount-box">
                                    <label class="form-label"><?php echo __('calc_amount'); ?></label>
                                    <input type="number" class="form-control" id="fromAmount" value="1" step="any" min="0">
                                </div>
                            </div>
                            
                            <div class="text-center my-2">
                                <button class="swap-btn" onclick="swapCurrencies()">
                                    <i class="bi bi-arrow-up-down"></i>
                                </button>
                            </div>
                            
                            <div class="currency-row mb-3">
                                <div class="flag-wrapper" id="toFlagWrapper">
                                    <span class="flag-icon flag-icon-id flag-icon-display"></span>
                                </div>
                                <div class="flex-grow">
                                    <label class="form-label"><?php echo __('calc_to'); ?></label>
                                    <select class="form-select" id="toCurrency">
                                        <?php foreach ($supportedCurrencies as $code): ?>
                                            <option value="<?php echo $code; ?>" <?php echo $code === 'IDR' ? 'selected' : ''; ?>>
                                                <?php echo getFlagClass($code); ?> <?php echo $code; ?> - <?php echo getCurrencyName($code); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="amount-box">
                                    <label class="form-label"><?php echo __('calc_result'); ?></label>
                                    <input type="text" class="form-control" id="toAmount" readonly style="font-weight:700; color:#4a6cf7;">
                                </div>
                            </div>
                            
                            <div class="rate-info mb-3" id="rateInfo">
                                Loading exchange rates...
                            </div>
                            
                            <button class="btn-convert" onclick="convertCurrency()">
                                <i class="bi bi-arrow-right"></i> <?php echo __('calc_convert'); ?>
                            </button>
                            
                            <button class="btn btn-outline-secondary btn-sm w-100 mt-2" onclick="refreshRates()">
                                <i class="bi bi-arrow-clockwise"></i> <?php echo __('calc_refresh_rates'); ?>
                            </button>
                            
                            <div class="mt-3">
                                <div class="card bg-light">
                                    <div class="card-header bg-secondary text-white py-2">
                                        <h6 class="mb-0"><i class="bi bi-clock-history"></i> <?php echo __('calc_currency_history'); ?></h6>
                                    </div>
                                    <div class="card-body p-2" style="max-height:150px; overflow-y:auto;" id="currencyHistoryList">
                                        <p class="text-muted text-center small mb-0"><?php echo __('calc_no_history'); ?></p>
                                    </div>
                                    <div class="card-footer p-1">
                                        <button class="btn btn-sm btn-danger w-100" onclick="clearCurrencyHistory()">
                                            <i class="bi bi-trash"></i> <?php echo __('calc_clear'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================================
// DATA MATA UANG
// ============================================================
const currenciesData = <?php 
    $currencyNames = [
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound',
        'IDR' => 'Indonesian Rupiah',
        'VND' => 'Vietnamese Dong',
        'JPY' => 'Japanese Yen',
        'KRW' => 'South Korean Won',
        'CNY' => 'Chinese Yuan',
        'SGD' => 'Singapore Dollar',
        'MYR' => 'Malaysian Ringgit',
        'THB' => 'Thai Baht',
        'PHP' => 'Philippine Peso',
        'AUD' => 'Australian Dollar',
        'NZD' => 'New Zealand Dollar',
        'CAD' => 'Canadian Dollar',
        'CHF' => 'Swiss Franc',
        'INR' => 'Indian Rupee',
        'SAR' => 'Saudi Riyal',
        'AED' => 'UAE Dirham',
        'TRY' => 'Turkish Lira',
        'RUB' => 'Russian Ruble',
        'BRL' => 'Brazilian Real',
        'ZAR' => 'South African Rand',
        'MXN' => 'Mexican Peso',
        'EGP' => 'Egyptian Pound',
        'PKR' => 'Pakistani Rupee',
        'BDT' => 'Bangladeshi Taka',
        'LKR' => 'Sri Lankan Rupee',
        'NPR' => 'Nepalese Rupee',
        'NGN' => 'Nigerian Naira',
        'KES' => 'Kenyan Shilling',
        'GHS' => 'Ghanaian Cedi',
        'TND' => 'Tunisian Dinar',
        'DZD' => 'Algerian Dinar',
        'MAD' => 'Moroccan Dirham',
        'QAR' => 'Qatari Riyal',
        'KWD' => 'Kuwaiti Dinar',
        'BHD' => 'Bahraini Dinar',
        'OMR' => 'Omani Rial',
        'JOD' => 'Jordanian Dinar',
    ];
    $result = [];
    foreach ($supportedCurrencies as $code) {
        $result[$code] = $currencyNames[$code] ?? $code;
    }
    echo json_encode($result);
?>;

// ============================================================
// KURS REAL-TIME DARI API
// ============================================================
const liveRates = <?php echo json_encode($rates); ?>;

// ============================================================
// KALKULATOR STANDARD
// ============================================================
let display = document.getElementById('display');
let history = document.getElementById('history');
let historyList = document.getElementById('historyList');
let currentInput = '0';
let previousInput = '';
let operator = '';
let shouldResetDisplay = false;
let historyData = [];

const MSG_DIVIDE_ZERO = '<?php echo addslashes(__('calc_error_divide_zero')); ?>';
const MSG_NEGATIVE_SQRT = '<?php echo addslashes(__('calc_error_negative_sqrt')); ?>';

function appendNumber(number) {
    if (shouldResetDisplay) {
        currentInput = '';
        shouldResetDisplay = false;
    }
    if (currentInput === '0' && number !== '.') {
        currentInput = number;
    } else {
        if (currentInput.replace('-', '').replace('.', '').length >= 15) return;
        currentInput += number;
    }
    updateDisplay();
}

function appendDecimal() {
    if (shouldResetDisplay) {
        currentInput = '0';
        shouldResetDisplay = false;
    }
    if (!currentInput.includes('.')) {
        currentInput += '.';
    }
    updateDisplay();
}

function appendOperator(op) {
    if (operator && !shouldResetDisplay) {
        calculate();
    }
    previousInput = currentInput;
    operator = op;
    shouldResetDisplay = true;
    history.textContent = formatNumber(previousInput) + ' ' + getOperatorSymbol(op);
}

function calculate() {
    if (!operator || !previousInput) {
        if (currentInput) {
            history.textContent = formatNumber(currentInput) + ' =';
        }
        return;
    }
    
    const prev = parseFloat(previousInput);
    const curr = parseFloat(currentInput);
    
    if (isNaN(prev) || isNaN(curr)) return;
    
    let result;
    const opSymbol = getOperatorSymbol(operator);
    
    switch(operator) {
        case '+': result = prev + curr; break;
        case '-': result = prev - curr; break;
        case '*': result = prev * curr; break;
        case '/': 
            if (curr === 0) {
                alert(MSG_DIVIDE_ZERO);
                clearDisplay();
                return;
            }
            result = prev / curr; 
            break;
        case '%': result = prev % curr; break;
        default: return;
    }
    
    const historyText = formatNumber(prev) + ' ' + opSymbol + ' ' + formatNumber(curr) + ' = ' + formatNumber(result);
    addHistory(historyText);
    history.textContent = historyText;
    currentInput = result.toString();
    updateDisplay();
    previousInput = '';
    operator = '';
    shouldResetDisplay = true;
}

function clearDisplay() {
    currentInput = '0';
    previousInput = '';
    operator = '';
    shouldResetDisplay = false;
    history.textContent = '';
    updateDisplay();
}

function backspace() {
    if (currentInput.length > 1) {
        currentInput = currentInput.slice(0, -1);
    } else {
        currentInput = '0';
    }
    updateDisplay();
}

function squareRoot() {
    const num = parseFloat(currentInput);
    if (num < 0) {
        alert(MSG_NEGATIVE_SQRT);
        return;
    }
    const result = Math.sqrt(num);
    addHistory('√' + formatNumber(num) + ' = ' + formatNumber(result));
    currentInput = result.toString();
    history.textContent = '√' + formatNumber(num) + ' = ' + formatNumber(result);
    shouldResetDisplay = true;
    updateDisplay();
}

function square() {
    const num = parseFloat(currentInput);
    const result = num * num;
    addHistory('(' + formatNumber(num) + ')² = ' + formatNumber(result));
    currentInput = result.toString();
    history.textContent = '(' + formatNumber(num) + ')² = ' + formatNumber(result);
    shouldResetDisplay = true;
    updateDisplay();
}

function toggleSign() {
    if (currentInput !== '0') {
        if (currentInput.startsWith('-')) {
            currentInput = currentInput.slice(1);
        } else {
            currentInput = '-' + currentInput;
        }
        updateDisplay();
    }
}

function updateDisplay() {
    display.textContent = formatNumber(currentInput);
}

function formatNumber(num) {
    if (num === '') return '0';
    const parts = num.toString().split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return parts.join(',');
}

function getOperatorSymbol(op) {
    const symbols = {'+': '+', '-': '−', '*': '×', '/': '÷', '%': '%'};
    return symbols[op] || op;
}

function addHistory(text) {
    historyData.push(text);
    if (historyData.length > 20) historyData.shift();
    updateHistoryList();
}

function updateHistoryList() {
    if (historyData.length === 0) {
        historyList.innerHTML = '<p class="text-muted text-center small mb-0"><?php echo __('calc_no_history'); ?></p>';
        return;
    }
    let html = '';
    for (let i = historyData.length - 1; i >= 0; i--) {
        html += '<div class="border-bottom py-1 small">' + historyData[i] + '</div>';
    }
    historyList.innerHTML = html;
}

function clearHistory() {
    historyData = [];
    updateHistoryList();
}

// ============================================================
// CURRENCY CONVERTER
// ============================================================
let currencyHistory = [];

function getFlagClass(currencyCode) {
    const flags = {
        'USD': 'us', 'EUR': 'eu', 'GBP': 'gb', 'IDR': 'id',
        'VND': 'vn', 'JPY': 'jp', 'KRW': 'kr', 'CNY': 'cn',
        'SGD': 'sg', 'MYR': 'my', 'THB': 'th', 'PHP': 'ph',
        'AUD': 'au', 'NZD': 'nz', 'CAD': 'ca', 'CHF': 'ch',
        'INR': 'in', 'SAR': 'sa', 'AED': 'ae', 'TRY': 'tr',
        'RUB': 'ru', 'BRL': 'br', 'ZAR': 'za', 'MXN': 'mx',
        'EGP': 'eg', 'PKR': 'pk', 'BDT': 'bd', 'LKR': 'lk',
        'NPR': 'np', 'NGN': 'ng', 'KES': 'ke', 'GHS': 'gh',
        'TND': 'tn', 'DZD': 'dz', 'MAD': 'ma', 'QAR': 'qa',
        'KWD': 'kw', 'BHD': 'bh', 'OMR': 'om', 'JOD': 'jo'
    };
    return flags[currencyCode] || 'us';
}

function getCurrencySymbolClient(currency) {
    const symbols = {
        'USD': '$', 'EUR': '€', 'GBP': '£', 'IDR': 'Rp',
        'VND': '₫', 'JPY': '¥', 'KRW': '₩', 'CNY': '¥',
        'SGD': 'S$', 'MYR': 'RM', 'THB': '฿', 'PHP': '₱',
        'AUD': 'A$', 'NZD': 'NZ$', 'CAD': 'C$', 'CHF': 'CHF',
        'INR': '₹', 'SAR': '﷼', 'AED': 'د.إ', 'TRY': '₺',
        'RUB': '₽', 'BRL': 'R$', 'ZAR': 'R', 'MXN': 'MX$',
        'EGP': 'E£', 'PKR': '₨', 'BDT': '৳', 'LKR': 'Rs',
        'NPR': 'Rs', 'NGN': '₦', 'KES': 'KSh', 'GHS': 'GH₵',
        'TND': 'د.ت', 'DZD': 'د.ج', 'MAD': 'د.م.', 'QAR': '﷼',
        'KWD': 'د.ك', 'BHD': 'د.ب', 'OMR': '﷼', 'JOD': 'د.ا'
    };
    return symbols[currency] || '';
}

function updateFlags() {
    const fromCurrency = document.getElementById('fromCurrency').value;
    const toCurrency = document.getElementById('toCurrency').value;
    
    const fromFlag = document.getElementById('fromFlagWrapper').querySelector('.flag-icon');
    const toFlag = document.getElementById('toFlagWrapper').querySelector('.flag-icon');
    
    if (fromFlag) {
        fromFlag.className = 'flag-icon flag-icon-' + getFlagClass(fromCurrency) + ' flag-icon-display';
    }
    if (toFlag) {
        toFlag.className = 'flag-icon flag-icon-' + getFlagClass(toCurrency) + ' flag-icon-display';
    }
}

function swapCurrencies() {
    const fromSelect = document.getElementById('fromCurrency');
    const toSelect = document.getElementById('toCurrency');
    const fromVal = fromSelect.value;
    const toVal = toSelect.value;
    
    fromSelect.value = toVal;
    toSelect.value = fromVal;
    
    updateFlags();
    convertCurrency();
}

function convertCurrency() {
    const fromCurrency = document.getElementById('fromCurrency').value;
    const toCurrency = document.getElementById('toCurrency').value;
    const amount = parseFloat(document.getElementById('fromAmount').value) || 0;
    
    if (amount <= 0) {
        document.getElementById('toAmount').value = '0';
        document.getElementById('rateInfo').textContent = 'Please enter a valid amount';
        return;
    }
    
    const rateInUSD = 1 / liveRates[fromCurrency];
    const rateToTarget = liveRates[toCurrency];
    const result = amount * rateInUSD * rateToTarget;
    
    let formattedResult;
    if (['IDR', 'VND', 'KRW', 'JPY'].includes(toCurrency)) {
        formattedResult = new Intl.NumberFormat('id-ID').format(Math.round(result));
    } else {
        formattedResult = result.toFixed(2);
    }
    
    document.getElementById('toAmount').value = formattedResult;
    
    const rate = 1 / liveRates[fromCurrency] * liveRates[toCurrency];
    document.getElementById('rateInfo').textContent = 
        `1 ${fromCurrency} = ${rate.toFixed(4)} ${toCurrency}`;
    
    const fromSymbol = getCurrencySymbolClient(fromCurrency);
    const toSymbol = getCurrencySymbolClient(toCurrency);
    const historyText = `${fromSymbol}${amount} ${fromCurrency} = ${toSymbol}${formattedResult} ${toCurrency}`;
    addCurrencyHistory(historyText);
}

function refreshRates() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Updating...';
    
    fetch('includes/refresh_rates.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Object.assign(liveRates, data.rates);
                document.querySelector('.rate-update-info strong').textContent = data.last_update;
                convertCurrency();
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Updated!';
                setTimeout(() => {
                    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> <?php echo __('calc_refresh_rates'); ?>';
                    btn.disabled = false;
                }, 2000);
            } else {
                alert('Failed to refresh rates. Please try again.');
                btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> <?php echo __('calc_refresh_rates'); ?>';
                btn.disabled = false;
            }
        })
        .catch(error => {
            alert('Error refreshing rates: ' + error);
            btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> <?php echo __('calc_refresh_rates'); ?>';
            btn.disabled = false;
        });
}

function addCurrencyHistory(text) {
    currencyHistory.push(text);
    if (currencyHistory.length > 20) currencyHistory.shift();
    updateCurrencyHistoryList();
}

function updateCurrencyHistoryList() {
    const list = document.getElementById('currencyHistoryList');
    if (currencyHistory.length === 0) {
        list.innerHTML = '<p class="text-muted text-center small mb-0"><?php echo __('calc_no_history'); ?></p>';
        return;
    }
    let html = '';
    for (let i = currencyHistory.length - 1; i >= 0; i--) {
        html += '<div class="border-bottom py-1 small">' + currencyHistory[i] + '</div>';
    }
    list.innerHTML = html;
}

function clearCurrencyHistory() {
    currencyHistory = [];
    updateCurrencyHistoryList();
}

// ============================================================
// EVENT LISTENERS
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('fromCurrency').addEventListener('change', function() {
        updateFlags();
        convertCurrency();
    });
    
    document.getElementById('toCurrency').addEventListener('change', function() {
        updateFlags();
        convertCurrency();
    });
    
    document.getElementById('fromAmount').addEventListener('input', convertCurrency);
    
    updateFlags();
    convertCurrency();
});

// ============================================================
// KEYBOARD SUPPORT (Standard Calculator)
// ============================================================
document.addEventListener('keydown', function(e) {
    if (!document.getElementById('standard-tab').classList.contains('active')) return;
    
    if (e.key >= '0' && e.key <= '9') appendNumber(e.key);
    else if (e.key === '.') appendDecimal();
    else if (['+', '-', '*', '/'].includes(e.key)) appendOperator(e.key);
    else if (e.key === 'Enter' || e.key === '=') { e.preventDefault(); calculate(); }
    else if (e.key === 'Backspace') backspace();
    else if (e.key === 'Escape' || e.key === 'c' || e.key === 'C') clearDisplay();
    else if (e.key === '%') appendOperator('%');
});

updateDisplay();
</script>

<?php include 'includes/footer.php'; ?>