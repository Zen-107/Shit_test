<?php
// กำหนด Header ให้ Browser รู้ว่าเป็น JSON response
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// สมมติว่าไฟล์ config.php มีการเชื่อมต่อฐานข้อมูล $pdo (PDO Object)
require_once 'config.php'; 

// ----------------------------------------------------
// 1. ตรวจสอบว่า $pdo ถูกกำหนดค่าและเป็น Object หรือไม่
// ----------------------------------------------------
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $response['message'] = 'Database connection object ($pdo) is not available. Check config.php.';
    echo json_encode($response);
    exit;
}
// ใช้ try...catch เพื่อจัดการ Error ของ PDO
try {

    // ----------------------------------------------------
    // 2. รับข้อมูลข้อความจาก ฟอร์ม
    // ----------------------------------------------------
    $author_id = (int) ($_POST['author_id'] ?? 0); 
    $story_title = $_POST['story_title'] ?? null;
    $body_text = $_POST['body_text'] ?? null; 
    $tags_text = $_POST['tags_text'] ?? null;

    if ($author_id === 0 || !$story_title || !$body_text) {
        $response['message'] = 'Missing required text data (Author ID, Title, or Body).';
        echo json_encode($response);
        exit;
    }

    // ----------------------------------------------------
    // 3. การจัดการไฟล์รูปภาพและการกำหนด Path
    // ----------------------------------------------------
    if (!isset($_FILES['cover_file']) || $_FILES['cover_file']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'No cover file uploaded or file error occurred. (Error: ' . ($_FILES['cover_file']['error'] ?? 'N/A') . ')';
        echo json_encode($response);
        exit;
    }

    $file = $_FILES['cover_file'];
    
    // กำหนด Path อัปโหลดจริงบน Server (เหมือนเดิม)
    $base_upload_dir = __DIR__ . '/../assets/img/'; 

    if (!is_dir($base_upload_dir)) {
        if (!mkdir($base_upload_dir, 0777, true)) {
            $response['message'] = 'Failed to create base upload directory. Check permissions: ' . $base_upload_dir;
            echo json_encode($response);
            exit;
        }
    }

    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_file_name = 'story_' . $author_id . '_' . uniqid() . '.' . $file_ext; 
    $target_file = $base_upload_dir . $new_file_name;
    $db_image_path = null; 

    // ----------------------------------------------------
    // 4. ย้ายไฟล์และบันทึกข้อมูลลง DB (ใช้ PDO)
    // ----------------------------------------------------
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        
        // Path สัมพัทธ์สำหรับเรียกใช้ใน HTML (บันทึกใน DB)
        $db_image_path = 'assets/img/' . $new_file_name;

        // 5. บันทึกข้อมูลทั้งหมดลงในตาราง stories ด้วย PDO
        $sql = "INSERT INTO stories (cover_image, story_title, body_text, author_id, tags_text) 
                VALUES (:cover_image, :story_title, :body_text, :author_id, :tags_text)";
        
        $stmt = $pdo->prepare($sql);
        
        // **ผูกพารามิเตอร์ (Bind Parameters) ด้วยชื่อใน PDO**
        $stmt->bindParam(':cover_image', $db_image_path);
        $stmt->bindParam(':story_title', $story_title);
        $stmt->bindParam(':body_text', $body_text);
        $stmt->bindParam(':author_id', $author_id, PDO::PARAM_INT); // ระบุว่าเป็น Integer
        $stmt->bindParam(':tags_text', $tags_text);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['story_id'] = $pdo->lastInsertId(); // ใช้ lastInsertId() ของ PDO
            $response['cover_image_path'] = $db_image_path;
            $response['message'] = 'Story and cover image saved successfully.';
        } else {
            // ดึง Error Info ของ PDO
            $errorInfo = $stmt->errorInfo();
            $response['message'] = 'Database query failed: ' . ($errorInfo[2] ?? 'Unknown PDO error');
            // หากบันทึก DB ล้มเหลว ให้ลบไฟล์ที่อัปโหลดไปแล้วออก
            unlink($target_file); 
        }

    } else {
        // ล้มเหลวในการย้ายไฟล์
        $response['message'] = 'Failed to move uploaded file. Check folder permissions (0777).';
    }

} catch (PDOException $e) {
    // ดักจับ Error ที่เกี่ยวข้องกับ DB โดยตรง
    $response['message'] = 'Database error (PDOException): ' . $e->getMessage();
} catch (Exception $e) {
    // ดักจับ Error อื่นๆ
    $response['message'] = 'General error: ' . $e->getMessage();
}

// ใน PDO ไม่จำเป็นต้องเรียก $pdo->close() เหมือน MySQLi
echo json_encode($response);
?>