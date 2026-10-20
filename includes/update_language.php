<?php
// includes/update_language.php
session_start();
require_once 'config/database.php';
require_once 'lang.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['language'])) {
    $language = $_POST['language'];
    $available_langs = ['en', 'id', 'vi', 'ja'];
    
    if (!in_array($language, $available_langs)) {
        echo json_encode(['success' => false, 'error' => 'Invalid language']);
        exit;
    }
    
    // Update session
    $_SESSION['language'] = $language;
    $_SESSION['currency_symbol'] = getCurrencySymbol($language);
    $_SESSION['currency_code'] = getCurrencyCode($language);
    
    // Update database
    $stmt = $conn->prepare("UPDATE users SET prefered_language = ? WHERE id = ?");
    $stmt->bind_param("si", $language, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    
    // Load translations for response
    $lang_data = loadLanguage($language);
    
    // Get all translated strings needed for header
    $translations = [
        'app_name' => __('app_name'),
        'dashboard' => __('dashboard'),
        'add' => __('add'),
        'savings' => __('savings'),
        'report' => __('report'),
        'calculator' => __('calculator'),
        'profile' => __('profile'),
        'logout' => __('logout'),
        'total_income' => __('total_income'),
        'total_expense' => __('total_expense'),
        'total_savings' => __('total_savings'),
        'total_balance' => __('total_balance'),
        'total_transactions' => __('total_transactions'),
        'active_targets' => __('active_targets'),
        'view_targets' => __('view_targets'),
        'recent_transactions' => __('recent_transactions'),
        'trend_chart' => __('trend_chart'),
        'expense_chart' => __('expense_chart'),
        'add_transaction' => __('add_transaction'),
        'savings_summary' => __('savings_summary'),
        'today_summary' => __('today_summary'),
        'today_transactions' => __('today_transactions'),
        'no_transactions_today' => __('no_transactions_today'),
        'income' => __('income'),
        'expense' => __('expense'),
        'transactions' => __('transactions'),
        'date' => __('date'),
        'type' => __('type'),
        'category' => __('category'),
        'amount' => __('amount'),
        'description' => __('description'),
        'actions' => __('actions'),
        'add' => __('add'),
        'no_data' => __('no_data'),
        'confirm_delete' => __('confirm_delete'),
        'balance' => __('balance'),
        'saved' => __('saved'),
        'withdrawn' => __('withdrawn'),
        'currency_symbol' => getCurrencySymbol($language),
        'currency_code' => getCurrencyCode($language),
    ];
    
    echo json_encode([
        'success' => true, 
        'language' => $language,
        'translations' => $translations
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit;
?>