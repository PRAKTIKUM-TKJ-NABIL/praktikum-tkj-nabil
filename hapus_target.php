<?php
require_once 'config/database.php';
require_once 'includes/auth_check.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Ambil data gambar untuk dihapus
    $stmt = $conn->prepare("SELECT gambar FROM target_tabungan WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $target = $result->fetch_assoc();
    $stmt->close();
    
    // Hapus gambar jika ada
    if ($target && !empty($target['gambar'])) {
        $file_path = "assets/images/target/" . $target['gambar'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Hapus target
    $stmt = $conn->prepare("DELETE FROM target_tabungan WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $stmt->close();
}

header('Location: target.php');
exit;
?>