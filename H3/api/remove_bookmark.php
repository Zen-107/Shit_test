<?php
include 'config.php'; // แน่ใจว่าไฟล์นี้ไม่มี error และไม่พิมพ์อะไร

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาล็อกอินก่อน']);
    exit;
}

$user_id = $_SESSION['user_id'];

// แก้ไขตรงนี้เพื่ออ่าน JSON
$input = json_decode(file_get_contents('php://input'), true);
$product_id = intval($input['product_id'] ?? 0);
$folder_id = intval($input['folder_id'] ?? null);


if ($product_id <= 0) {
    http_response_code(400); //case 400: $text = 'Bad Request'
    echo json_encode(['success' => false, 'message' => 'ID สินค้าไม่ถูกต้อง']);
    exit;
}

// ลบเฉพาะใน folder ที่ระบุ
if ($folder_id) {
    $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND product_id = ? AND folder_id = ?");
    $stmt->execute([$user_id, $product_id, $folder_id]);
} else {
    // ถ้าไม่ระบุ folder_id → ลบบุ๊กมาร์กทั้งหมดของสินค้านี้ (ไม่ว่า folder ไหน)
    $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
}

echo json_encode(["success" => true, "message" => "ลบบุ๊กมาร์กสำเร็จ"]);