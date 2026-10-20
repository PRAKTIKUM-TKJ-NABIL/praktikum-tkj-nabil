<?php
require_once __DIR__ . '/config/database.php';
require_once 'includes/auth_check.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Hapus dengan user_id untuk keamanan
    $stmt = $conn->prepare("DELETE FROM catatan_keuangan WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $stmt->close();
}

header('Location: dashboard.php');
exit;
?>