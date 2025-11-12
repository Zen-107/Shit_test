<?php
require_once 'db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid product ID");
}
$product_id = (int)$_GET['id'];

$message = '';

// ดึงข้อมูลสินค้า
$stmt = $pdo->prepare("SELECT id, name, description, image_url FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("ไม่พบสินค้านี้");
}

// ดึง external URLs แบบเต็ม (รวมช่วงราคา)
$external_urls = [];
$stmt = $pdo->prepare("SELECT url, min_price, max_price, currency FROM product_external_urls WHERE product_id = ?");
$stmt->execute([$product_id]);
while ($row = $stmt->fetch()) {
    $external_urls[] = $row;
}

// ดึงหมวดหมู่และ interests ที่เลือก
$selected_categories = [];
$stmt = $pdo->prepare("SELECT category_id FROM product_categories WHERE product_id = ?");
$stmt->execute([$product_id]);
while ($row = $stmt->fetch()) {
    $selected_categories[] = $row['category_id'];
}

$selected_interests = [];
$stmt = $pdo->prepare("SELECT interest_id FROM product_interests WHERE product_id = ?");
$stmt->execute([$product_id]);
while ($row = $stmt->fetch()) {
    $selected_interests[] = $row['interest_id'];
}

// ดึงรายการหมวดหมู่และ interests ทั้งหมด
$categories_list = $interests_list = [];
$cat_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories_list = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
$int_stmt = $pdo->query("SELECT id, name FROM interests ORDER BY name");
$interests_list = $int_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $image_url = trim($_POST['image_url']);

        if (empty($name)) throw new Exception("กรุณากรอกชื่อสินค้า");

        $pdo->beginTransaction();

        // อัปเดตสินค้า (ไม่มี price/currency)
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, image_url = ? WHERE id = ?");
        $stmt->execute([$name, $description, $image_url, $product_id]);

        // ลบลิงก์ร้านค้าเดิม
        $pdo->prepare("DELETE FROM product_external_urls WHERE product_id = ?")->execute([$product_id]);

        // เพิ่มลิงก์ใหม่ (รวมช่วงราคา)
        if (!empty($_POST['external_urls']) && is_array($_POST['external_urls'])) {
            foreach ($_POST['external_urls'] as $idx => $url) {
                $url = trim($url);
                if (empty($url)) continue;

                $min_price = isset($_POST['min_prices'][$idx]) ? (float)$_POST['min_prices'][$idx] : 0.0;
                $max_price = isset($_POST['max_prices'][$idx]) ? (float)$_POST['max_prices'][$idx] : $min_price;
                $currency = trim($_POST['currencies'][$idx] ?? 'THB') ?: 'THB';

                if ($min_price > $max_price) {
                    throw new Exception("ช่วงราคาไม่ถูกต้อง: min ต้อง ≤ max");
                }

                $host = parse_url($url, PHP_URL_HOST);
                $source_name = 'Unknown';
                if ($host) {
                    if (strpos($host, 'shopee') !== false) $source_name = 'Shopee';
                    elseif (strpos($host, 'lazada') !== false) $source_name = 'Lazada';
                    elseif (strpos($host, 'jd') !== false) $source_name = 'JD Central';
                    else $source_name = ucfirst(str_replace(['.co.th', '.com', 'www.'], '', $host));
                }

                $stmt = $pdo->prepare("INSERT INTO product_external_urls (product_id, url, source_name, min_price, max_price, currency) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$product_id, $url, $source_name, $min_price, $max_price, $currency]);
            }
        }

        // รีเซ็ตหมวดหมู่และ interests
        $pdo->prepare("DELETE FROM product_categories WHERE product_id = ?")->execute([$product_id]);
        $pdo->prepare("DELETE FROM product_interests WHERE product_id = ?")->execute([$product_id]);

        // จัดการหมวดหมู่ใหม่
        $selected_category_ids = $_POST['category_ids'] ?? [];
        $new_categories = $_POST['new_categories'] ?? [];
        foreach ($new_categories as $cat_name) {
            $cat_name = trim($cat_name);
            if (!empty($cat_name)) {
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$cat_name]);
                $exists = $stmt->fetch();
                $cat_id = $exists ? $exists['id'] : null;
                if (!$cat_id) {
                    $stmt->execute([$cat_name]);
                    $cat_id = $pdo->lastInsertId();
                }
                $stmt = $pdo->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
                $stmt->execute([$product_id, $cat_id]);
            }
        }
        foreach ($selected_category_ids as $cat_id) {
            $stmt = $pdo->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
            $stmt->execute([$product_id, (int)$cat_id]);
        }

        // จัดการ interests ใหม่
        $selected_interest_ids = $_POST['interest_ids'] ?? [];
        $new_interests = $_POST['new_interests'] ?? [];
        foreach ($new_interests as $int_name) {
            $int_name = trim($int_name);
            if (!empty($int_name)) {
                $stmt = $pdo->prepare("SELECT id FROM interests WHERE name = ?");
                $stmt->execute([$int_name]);
                $exists = $stmt->fetch();
                $int_id = $exists ? $exists['id'] : null;
                if (!$int_id) {
                    $stmt->execute([$int_name]);
                    $int_id = $pdo->lastInsertId();
                }
                $stmt = $pdo->prepare("INSERT INTO product_interests (product_id, interest_id) VALUES (?, ?)");
                $stmt->execute([$product_id, $int_id]);
            }
        }
        foreach ($selected_interest_ids as $int_id) {
            $stmt = $pdo->prepare("INSERT INTO product_interests (product_id, interest_id) VALUES (?, ?)");
            $stmt->execute([$product_id, (int)$int_id]);
        }

        $pdo->commit();
        $message = "<div class='alert alert-success'>แก้ไขสินค้าเรียบร้อย!</div>";

    } catch (Exception $e) {
        $pdo->rollback();
        $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขสินค้า - Gift Finder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .external-url-group { border: 1px solid #e0e0e0; padding: 15px; margin-bottom: 15px; border-radius: 8px; background: #f9f9f9; }
        .checkbox-group { margin-bottom: 8px; }
        .new-item-input { margin-top: 8px; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">✏️ แก้ไขสินค้า: <?= htmlspecialchars($product['name']) ?></h2>

    <?= $message ?>

    <form method="POST" class="bg-white p-4 rounded shadow">
        <div class="mb-3">
            <label class="form-label">ชื่อสินค้า *</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">คำอธิบาย</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">ลิงก์รูปภาพ (URL)</label>
            <input type="url" name="image_url" class="form-control" value="<?= htmlspecialchars($product['image_url']) ?>">
        </div>

        <!-- === ลิงก์ร้านค้าและช่วงราคา === -->
        <div class="mb-4">
            <label class="form-label d-block">ลิงก์ร้านค้าและช่วงราคา</label>
            <div id="external-url-container">
                <?php if (!empty($external_urls)): ?>
                    <?php foreach ($external_urls as $item): ?>
                        <div class="external-url-group">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">URL ร้านค้า *</label>
                                    <input type="url" name="external_urls[]" class="form-control" value="<?= htmlspecialchars($item['url']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ช่วงราคา (อัตโนมัติ)</label>
                                    <div class="form-control bg-light"><?= htmlspecialchars($item['min_price']) ?> – <?= htmlspecialchars($item['max_price']) ?> <?= htmlspecialchars($item['currency']) ?></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">ราคาต่ำสุด</label>
                                    <input type="number" step="0.01" name="min_prices[]" class="form-control" value="<?= $item['min_price'] ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ราคาสูงสุด</label>
                                    <input type="number" step="0.01" name="max_prices[]" class="form-control" value="<?= $item['max_price'] ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">สกุลเงิน</label>
                                    <input type="text" name="currencies[]" class="form-control" value="<?= htmlspecialchars($item['currency']) ?>" maxlength="10">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="external-url-group">
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label class="form-label">URL ร้านค้า *</label>
                                <input type="url" name="external_urls[]" class="form-control" placeholder="https://shopee.co.th/..." required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">ราคาต่ำสุด</label>
                                <input type="number" step="0.01" name="min_prices[]" class="form-control" value="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ราคาสูงสุด</label>
                                <input type="number" step="0.01" name="max_prices[]" class="form-control" value="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">สกุลเงิน</label>
                                <input type="text" name="currencies[]" class="form-control" value="THB" maxlength="10">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addExternalUrlGroup()">+ เพิ่มร้านค้าอีก</button>
        </div>

        <!-- === หมวดหมู่ === -->
        <div class="mb-3">
            <label class="form-label d-block">หมวดหมู่</label>
            <div class="form-check">
                <?php if (!empty($categories_list)): ?>
                    <?php foreach ($categories_list as $cat): ?>
                        <div class="checkbox-group">
                            <input class="form-check-input" type="checkbox" name="category_ids[]" value="<?= htmlspecialchars($cat['id']) ?>" 
                                   id="cat_<?= $cat['id'] ?>" 
                                   <?= in_array($cat['id'], $selected_categories) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="cat_<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="new-item-input">
                <label>เพิ่มหมวดหมู่ใหม่:</label>
                <input type="text" name="new_categories[]" class="form-control" placeholder="พิมพ์ชื่อหมวดหมู่ใหม่">
                <button type="button" class="btn btn-outline-secondary btn-sm mt-1" onclick="addNewCategory()">+ เพิ่มอีก</button>
            </div>
        </div>

        <!-- === ความสนใจ === -->
        <div class="mb-3">
            <label class="form-label d-block">ความสนใจ</label>
            <div class="form-check">
                <?php if (!empty($interests_list)): ?>
                    <?php foreach ($interests_list as $int): ?>
                        <div class="checkbox-group">
                            <input class="form-check-input" type="checkbox" name="interest_ids[]" value="<?= htmlspecialchars($int['id']) ?>" 
                                   id="int_<?= $int['id'] ?>" 
                                   <?= in_array($int['id'], $selected_interests) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="int_<?= $int['id'] ?>"><?= htmlspecialchars($int['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="new-item-input">
                <label>เพิ่มความสนใจใหม่:</label>
                <input type="text" name="new_interests[]" class="form-control" placeholder="พิมพ์ชื่อความสนใจใหม่">
                <button type="button" class="btn btn-outline-secondary btn-sm mt-1" onclick="addNewInterest()">+ เพิ่มอีก</button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
        <a href="manage_products.php" class="btn btn-secondary ms-2">ยกเลิก</a>
    </form>
</div>

<script>
function addExternalUrlGroup() {
    const container = document.getElementById('external-url-container');
    const newGroup = document.createElement('div');
    newGroup.className = 'external-url-group';
    newGroup.innerHTML = `
        <div class="row mb-2">
            <div class="col-md-6">
                <label class="form-label">URL ร้านค้า *</label>
                <input type="url" name="external_urls[]" class="form-control" placeholder="https://example.com/..." required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label class="form-label">ราคาต่ำสุด</label>
                <input type="number" step="0.01" name="min_prices[]" class="form-control" value="0.00">
            </div>
            <div class="col-md-4">
                <label class="form-label">ราคาสูงสุด</label>
                <input type="number" step="0.01" name="max_prices[]" class="form-control" value="0.00">
            </div>
            <div class="col-md-4">
                <label class="form-label">สกุลเงิน</label>
                <input type="text" name="currencies[]" class="form-control" value="THB" maxlength="10">
            </div>
        </div>
    `;
    container.appendChild(newGroup);
}

function addNewCategory() {
    const container = document.querySelector('input[name="new_categories[]"]').parentElement;
    const newInput = document.createElement('input');
    newInput.type = 'text';
    newInput.name = 'new_categories[]';
    newInput.className = 'form-control mt-1';
    newInput.placeholder = 'พิมพ์หมวดหมู่ใหม่';
    container.insertBefore(newInput, container.querySelector('button'));
}

function addNewInterest() {
    const container = document.querySelector('input[name="new_interests[]"]').parentElement;
    const newInput = document.createElement('input');
    newInput.type = 'text';
    newInput.name = 'new_interests[]';
    newInput.className = 'form-control mt-1';
    newInput.placeholder = 'พิมพ์ความสนใจใหม่';
    container.insertBefore(newInput, container.querySelector('button'));
}
</script>
</body>
</html>