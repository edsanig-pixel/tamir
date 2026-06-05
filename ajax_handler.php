<?php
require_once __DIR__ . '/includes/config.php';
require_login($pdo);

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';
$response = ['success' => false];

try {
    // ========== مشتری ==========
    if ($action === 'add_customer') {
        $name  = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        if (empty($name) || empty($phone)) throw new Exception('نام و تلفن اصلی الزامی است.');
        $phone2   = $_POST['phone2'] ?? null;
        $type     = $_POST['customer_type'] ?? 'personal';
        $company  = $_POST['company_name'] ?? null;
        $address  = $_POST['address'] ?? null;
        $email    = $_POST['email'] ?? null;
        $notes    = $_POST['notes'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO customers (name, phone, phone2, customer_type, company_name, address, email, notes) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$name, $phone, $phone2, $type, $company, $address, $email, $notes]);
        $id = $pdo->lastInsertId();
        log_activity($pdo, $_SESSION['user_id'], 'create', 'customer', $id, "افزودن مشتری $name");
        $response = ['success' => true, 'id' => $id, 'name' => $name];
    }
    elseif ($action === 'update_customer') {
        $id     = $_POST['id'] ?? 0;
        $name   = $_POST['name'] ?? '';
        $phone  = $_POST['phone'] ?? '';
        if (empty($id) || empty($name) || empty($phone)) throw new Exception('اطلاعات ناقص است.');
        $phone2   = $_POST['phone2'] ?? null;
        $type     = $_POST['customer_type'] ?? 'personal';
        $company  = $_POST['company_name'] ?? null;
        $address  = $_POST['address'] ?? null;
        $email    = $_POST['email'] ?? null;
        $notes    = $_POST['notes'] ?? null;
        $stmt = $pdo->prepare("UPDATE customers SET name=?, phone=?, phone2=?, customer_type=?, company_name=?, address=?, email=?, notes=? WHERE id=?");
        $stmt->execute([$name, $phone, $phone2, $type, $company, $address, $email, $notes, $id]);
        log_activity($pdo, $_SESSION['user_id'], 'update', 'customer', $id, "ویرایش مشتری $name");
        $response = ['success' => true, 'message' => 'مشتری با موفقیت به‌روز شد.'];
    }

    // ========== نوع دستگاه / خدمت / تکنسین / قطعه ==========
    elseif ($action === 'add_device_type') {
        $name = $_POST['name'] ?? ''; if (empty($name)) throw new Exception('نام نوع دستگاه الزامی است.');
        $stmt = $pdo->prepare("INSERT INTO device_types (name) VALUES (?)"); $stmt->execute([$name]);
        $id = $pdo->lastInsertId();
        log_activity($pdo, $_SESSION['user_id'], 'create', 'device_type', $id, "افزودن نوع دستگاه $name");
        $response = ['success' => true, 'id' => $id, 'name' => $name];
    }
    elseif ($action === 'add_service_type') {
        $name = $_POST['name'] ?? ''; if (empty($name)) throw new Exception('نام نوع خدمت الزامی است.');
        $stmt = $pdo->prepare("INSERT INTO service_types (name) VALUES (?)"); $stmt->execute([$name]);
        $id = $pdo->lastInsertId();
        log_activity($pdo, $_SESSION['user_id'], 'create', 'service_type', $id, "افزودن نوع خدمت $name");
        $response = ['success' => true, 'id' => $id, 'name' => $name];
    }
    elseif ($action === 'add_technician') {
        $name = $_POST['name'] ?? ''; if (empty($name)) throw new Exception('نام تکنسین الزامی است.');
        $stmt = $pdo->prepare("INSERT INTO technicians (name, phone) VALUES (?, '')"); $stmt->execute([$name]);
        $id = $pdo->lastInsertId();
        log_activity($pdo, $_SESSION['user_id'], 'create', 'technician', $id, "افزودن تکنسین $name");
        $response = ['success' => true, 'id' => $id, 'name' => $name];
    }
    elseif ($action === 'add_part') {
        $name = $_POST['name'] ?? ''; if (empty($name)) throw new Exception('نام قطعه الزامی است.');
        $stmt = $pdo->prepare("INSERT INTO parts (name, stock, default_purchase_price, default_sale_price) VALUES (?, 0, NULL, NULL)"); $stmt->execute([$name]);
        $id = $pdo->lastInsertId();
        log_activity($pdo, $_SESSION['user_id'], 'create', 'part', $id, "افزودن قطعه $name");
        $response = ['success' => true, 'id' => $id, 'name' => $name];
    }

    // ========== قطعه (قیمت‌ها) ==========
    elseif ($action === 'get_part_info') {
        $partId = $_POST['part_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT default_sale_price, default_purchase_price FROM parts WHERE id = ?"); $stmt->execute([$partId]);
        $part = $stmt->fetch();
        if ($part) {
            if (empty($part['default_sale_price'])) {
                $lp = $pdo->prepare("SELECT unit_price FROM repair_parts WHERE part_id = ? ORDER BY id DESC LIMIT 1"); $lp->execute([$partId]);
                $part['default_sale_price'] = $lp->fetchColumn() ?: 0;
            }
            $response = ['success' => true, 'default_sale_price' => $part['default_sale_price'] ?? 0, 'default_purchase_price' => $part['default_purchase_price'] ?? 0];
        } else $response = ['success' => false, 'message' => 'قطعه پیدا نشد'];
    }
    elseif ($action === 'get_last_part_price') {
        $partId = $_POST['part_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT unit_price FROM repair_parts WHERE part_id = ? ORDER BY id DESC LIMIT 1"); $stmt->execute([$partId]);
        $response = ['success' => true, 'unit_price' => $stmt->fetchColumn() ?: 0];
    }

    // ========== لاگ و بازگردانی ==========
    elseif ($action === 'get_log_data') {
        $logId = $_POST['log_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM activity_log WHERE id = ?"); $stmt->execute([$logId]);
        $log = $stmt->fetch();
        if (!$log) throw new Exception('لاگ با این شناسه وجود ندارد.');
        if (empty($log['description'])) throw new Exception('این لاگ اطلاعات قابل بازیابی ندارد.');
        $repair = json_decode($log['description'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($repair)) throw new Exception('فرمت داده‌های لاگ نامعتبر است.');
        if (!empty($repair['service_date'])) $repair['service_date_jalali'] = get_jalali_date_str(strtotime($repair['service_date']));
        if (!empty($repair['next_service_date'])) $repair['next_service_date_jalali'] = get_jalali_date_str(strtotime($repair['next_service_date']));
        if (!empty($repair['customer_id'])) {
            $cs = $pdo->prepare("SELECT name FROM customers WHERE id = ?"); $cs->execute([$repair['customer_id']]);
            $repair['customer_name'] = $cs->fetchColumn() ?: '';
        }
        $response = ['success' => true, 'repair' => $repair];
    }
    elseif ($action === 'restore_repair') {
        $logId = $_POST['log_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM activity_log WHERE id = ?"); $stmt->execute([$logId]);
        $log = $stmt->fetch();
        if (!$log || empty($log['description'])) throw new Exception('اطلاعات قبلی یافت نشد.');
        $oldData = json_decode($log['description'], true);
        if (!$oldData || !isset($oldData['id'])) throw new Exception('داده‌های قبلی معتبر نیستند.');
        $repairId = $oldData['id'];
        $allowed = ['id','customer_id','device_type_id','device_brand','device_model','device_serial','device_year','device_location','service_type_id','problem_part','fault_description','solution_description','technician_id','service_date','service_time','next_service_date','labor_cost','extra_costs','payment_status','invoice_number','final_status','technician_notes','warranty_months','created_by','updated_by'];
        $insertData = [];
        foreach ($allowed as $col) { if (array_key_exists($col, $oldData)) $insertData[$col] = $oldData[$col]; }
        if (empty($insertData)) throw new Exception('هیچ داده معتبری برای درج وجود ندارد.');
        $pdo->beginTransaction();
       $pdo->prepare("DELETE FROM repair_parts WHERE repair_id = ?")->execute([$repairId]);
	   $pdo->prepare("DELETE FROM repairs WHERE id = ?")->execute([$repairId]);
        $cols = array_keys($insertData); $ph = array_map(fn($c) => ":$c", $cols);
        $sql = "INSERT INTO repairs (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $ph) . ")";
        $stmt = $pdo->prepare($sql);
        foreach ($insertData as $c => $v) $stmt->bindValue(":$c", $v);
        $stmt->execute();
        $pdo->commit();
        log_activity($pdo, $_SESSION['user_id'], 'update', 'repair', $repairId, 'بازگردانی به نسخهٔ قبلی');
        $response = ['success' => true, 'message' => 'سرویس با موفقیت به حالت قبل بازگشت.'];
    }

    // ========== تأمین‌کننده ==========
    elseif ($action === 'add_supplier') {
        $name = $_POST['name'] ?? ''; if (empty($name)) throw new Exception('نام تأمین‌کننده الزامی است.');
        $phone = $_POST['phone'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, phone) VALUES (?, ?)"); $stmt->execute([$name, $phone]);
        $id = $pdo->lastInsertId();
        log_activity($pdo, $_SESSION['user_id'], 'create', 'supplier', $id, "افزودن تأمین‌کننده $name");
        $response = ['success' => true, 'id' => $id, 'name' => $name];
    }
    elseif ($action === 'update_supplier') {
        $id = $_POST['id'] ?? 0; $name = $_POST['name'] ?? ''; $phone = $_POST['phone'] ?? '';
        if (empty($id) || empty($name)) throw new Exception('اطلاعات ناقص است.');
        $stmt = $pdo->prepare("UPDATE suppliers SET name=?, phone=? WHERE id=?"); $stmt->execute([$name, $phone, $id]);
        log_activity($pdo, $_SESSION['user_id'], 'update', 'supplier', $id, "ویرایش تأمین‌کننده $name");
        $response = ['success' => true];
    }

    // ========== خرید ==========
    elseif ($action === 'add_purchase') {
        $supplierId = $_POST['supplier_id'] ?? 0;
        $date       = $_POST['date'] ?? '';
        $invoice    = $_POST['invoice'] ?? '';
        $notes      = $_POST['notes'] ?? '';
        $itemsJson  = $_POST['items'] ?? '';
        if (empty($supplierId) || empty($date)) throw new Exception('تأمین‌کننده و تاریخ الزامی است.');
        $items = json_decode($itemsJson, true);
        if (!is_array($items) || count($items) == 0) throw new Exception('حداقل یک قطعه الزامی است.');

        $pdo->beginTransaction();
        // درج فاکتور خرید
        $stmt = $pdo->prepare("INSERT INTO purchases (supplier_id, invoice_number, date, notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$supplierId, $invoice, $date, $notes]);
        $purchaseId = $pdo->lastInsertId();

        $totalAmount = 0;
        $itemStmt = $pdo->prepare("INSERT INTO purchase_items (purchase_id, part_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
        $updateStock = $pdo->prepare("UPDATE parts SET stock = stock + ? WHERE id = ?");
        foreach ($items as $item) {
            $partId = $item['part_id'] ?? 0;
            $qty    = $item['quantity'] ?? 1;
            $price  = $item['unit_price'] ?? 0;
            $itemStmt->execute([$purchaseId, $partId, $qty, $price]);
            $updateStock->execute([$qty, $partId]);
            $totalAmount += $qty * $price;
        }
        // به‌روزرسانی مجموع فاکتور
        $pdo->prepare("UPDATE purchases SET total_amount = ? WHERE id = ?")->execute([$totalAmount, $purchaseId]);

        // ثبت تراکنش هزینه (پرداخت به تأمین‌کننده)
        $transStmt = $pdo->prepare("INSERT INTO transactions (type, amount, description, date, supplier_id, related_purchase_id) VALUES ('expense', ?, ?, ?, ?, ?)");
        $transStmt->execute([$totalAmount, "خرید از تأمین‌کننده (فاکتور $invoice)", $date, $supplierId, $purchaseId]);

        $pdo->commit();
        log_activity($pdo, $_SESSION['user_id'], 'create', 'purchase', $purchaseId, "ثبت فاکتور خرید $invoice");
        $response = ['success' => true, 'id' => $purchaseId];
    }

    // ========== تراکنش مالی (دریافت از مشتری) ==========
    elseif ($action === 'add_transaction') {
        $customerId = $_POST['customer_id'] ?? 0;
        $amount     = str_replace(',', '', $_POST['amount'] ?? '0');
        $date       = $_POST['date'] ?? '';
        $repairId   = $_POST['repair_id'] ?? null;
        $desc       = $_POST['desc'] ?? '';

        if (empty($customerId) || empty($amount) || empty($date)) throw new Exception('اطلاعات تراکنش ناقص است.');
        $stmt = $pdo->prepare("INSERT INTO transactions (type, amount, description, date, customer_id, related_repair_id) VALUES ('income', ?, ?, ?, ?, ?)");
        $stmt->execute([$amount, $desc, $date, $customerId, $repairId ?: null]);
        log_activity($pdo, $_SESSION['user_id'], 'create', 'transaction', $pdo->lastInsertId(), "دریافت از مشتری");
        $response = ['success' => true, 'message' => 'پرداخت با موفقیت ثبت شد.'];
    }

    else {
        $response = ['success' => false, 'message' => 'عملیات نامشخص'];
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);