<?php
require_once 'db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_products.php?error=invalid_id");
    exit;
}

$product_id = (int)$_GET['id'];

try {
    // การลบจะ cascade ไปลบ `product_categories`, `product_interests`, `product_external_urls` อัตโนมัติ
    // เพราะคุณตั้ง `ON DELETE CASCADE` ไว้แล้ว
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);

    header("Location: manage_products.php?success=deleted");
} catch (Exception $e) {
    header("Location: manage_products.php?error=delete_failed");
}
exit;
?>