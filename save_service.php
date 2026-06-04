<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
require_login($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'متد غیرمجاز']);
    exit;
}

try {
    $customerId = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
    $deviceTypeId = filter_input(INPUT_POST, 'device_type_id', FILTER_VALIDATE_INT);
    $serviceTypeId = filter_input(INPUT_POST, 'service_type_id', FILTER_VALIDATE_INT);
    $technicianId = filter_input(INPUT_POST, 'technician_id', FILTER_VALIDATE_INT);
    $paymentStatus = $_POST['payment_status'] ?? 'unpaid';
    $laborCost = (int)str_replace([',', ' '], '', $_POST['labor_cost'] ?? '0');
    $extraCosts = (int)str_replace([',', ' '], '', $_POST['extra_costs'] ?? '0');
    $warrantyMonths = $_POST['warranty_months'] !== '' ? (int)$_POST['warranty_months'] : null;

    if (!$customerId || $customerId < 1) {
        throw new Exception('مشتری انتخاب نشده است.');
    }
    if (!$serviceTypeId || $serviceTypeId < 1) {
        throw new Exception('نوع خدمت انتخاب نشده است.');
    }
    if (empty($_POST['service_date']) || !validate_jalali_date($_POST['service_date'])) {
        throw new Exception('تاریخ سرویس نامعتبر است.');
    }
    if (!empty($_POST['next_service_date']) && !validate_jalali_date($_POST['next_service_date'])) {
        throw new Exception('تاریخ سرویس بعدی نامعتبر است.');
    }
    if (!in_array($paymentStatus, ['paid', 'unpaid', 'partial'], true)) {
        $paymentStatus = 'unpaid';
    }

    $serviceDate = convert_jalali_to_gregorian_date($_POST['service_date']);
    $nextServiceDate = !empty($_POST['next_service_date']) ? convert_jalali_to_gregorian_date($_POST['next_service_date']) : null;
    if ($serviceDate === null) {
        throw new Exception('تاریخ سرویس نامعتبر است.');
    }

    $invoice = generate_invoice_number($pdo);
    $userId = $_SESSION['user_id'];

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO repairs 
        (customer_id, device_type_id, device_brand, device_model, device_serial, device_year, device_location,
         service_type_id, problem_part, fault_description, technician_id, service_date, service_time,
         next_service_date, labor_cost, extra_costs, payment_status, invoice_number, final_status,
         solution_description, technician_notes, warranty_months, created_by, updated_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $customerId,
        $deviceTypeId,
        trim($_POST['device_brand'] ?? ''),
        trim($_POST['device_model'] ?? ''),
        trim($_POST['device_serial'] ?? ''),
        $_POST['device_year'] !== '' ? (int)$_POST['device_year'] : null,
        trim($_POST['device_location'] ?? ''),
        $serviceTypeId,
        trim($_POST['problem_part'] ?? ''),
        trim($_POST['fault_description'] ?? ''),
        $technicianId ?: null,
        $serviceDate,
        $_POST['service_time'] ?? null,
        $nextServiceDate,
        $laborCost,
        $extraCosts,
        $paymentStatus,
        $invoice,
        trim($_POST['final_status'] ?? ''),
        trim($_POST['solution_description'] ?? ''),
        trim($_POST['technician_notes'] ?? ''),
        $warrantyMonths,
        $userId,
        $userId
    ]);
    $repairId = $pdo->lastInsertId();

    // ثبت قطعات مصرفی + کاهش موجودی
    if (!empty($_POST['parts'])) {
        $parts = json_decode($_POST['parts'], true);
        if (is_array($parts)) {
            $partStmt = $pdo->prepare("INSERT INTO repair_parts (repair_id, part_id, quantity, unit_price, purchase_price) VALUES (?,?,?,?,?)");
            $updateStock = $pdo->prepare("UPDATE parts SET stock = stock - ? WHERE id = ? AND stock IS NOT NULL");
            foreach ($parts as $part) {
                $partId = filter_var($part['part_id'] ?? 0, FILTER_VALIDATE_INT);
                if (!$partId || $partId < 1) {
                    continue;
                }
                $qty = filter_var($part['quantity'] ?? 1, FILTER_VALIDATE_INT);
                $qty = $qty && $qty > 0 ? $qty : 1;
                $unitPrice = filter_var($part['unit_price'] ?? 0, FILTER_VALIDATE_INT);
                $purchasePrice = filter_var($part['purchase_price'] ?? 0, FILTER_VALIDATE_INT);
                $partStmt->execute([
                    $repairId,
                    $partId,
                    $qty,
                    $unitPrice ?: 0,
                    $purchasePrice ?: 0
                ]);
                // کاهش موجودی
                $updateStock->execute([$qty, $partId]);
            }
        }
    }

    $pdo->commit();

    // ثبت لاگ بعد از commit
    log_activity($pdo, $userId, 'create', 'repair', $repairId, "ایجاد سرویس با فاکتور $invoice");

    echo json_encode(['success' => true, 'invoice_number' => $invoice]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}