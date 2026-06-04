<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
require_login($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'متد غیرمجاز']);
    exit;
}

try {
    $pdo->beginTransaction();

    $invoice = generate_invoice_number($pdo);

    $serviceDate = null;
    if (!empty($_POST['service_date'])) {
        $parts = explode('/', $_POST['service_date']);
        if (count($parts) == 3) {
            $g = jalali_to_gregorian($parts[0], $parts[1], $parts[2]);
            $serviceDate = sprintf("%04d-%02d-%02d", $g[0], $g[1], $g[2]);
        }
    }
    $nextServiceDate = null;
    if (!empty($_POST['next_service_date'])) {
        $parts = explode('/', $_POST['next_service_date']);
        if (count($parts) == 3) {
            $g = jalali_to_gregorian($parts[0], $parts[1], $parts[2]);
            $nextServiceDate = sprintf("%04d-%02d-%02d", $g[0], $g[1], $g[2]);
        }
    }

    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO repairs 
        (customer_id, device_type_id, device_brand, device_model, device_serial, device_year, device_location,
         service_type_id, problem_part, fault_description, technician_id, service_date, service_time,
         next_service_date, labor_cost, extra_costs, payment_status, invoice_number, final_status,
         solution_description, technician_notes, warranty_months, created_by, updated_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['customer_id'] ?: null,
        $_POST['device_type_id'] ?: null,
        $_POST['device_brand'] ?? '',
        $_POST['device_model'] ?? '',
        $_POST['device_serial'] ?? '',
        $_POST['device_year'] ?? null,
        $_POST['device_location'] ?? '',
        $_POST['service_type_id'] ?: null,
        $_POST['problem_part'] ?? '',
        $_POST['fault_description'] ?? '',
        $_POST['technician_id'] ?: null,
        $serviceDate,
        $_POST['service_time'] ?? null,
        $nextServiceDate,
        $_POST['labor_cost'] ?? 0,
        $_POST['extra_costs'] ?? 0,
        $_POST['payment_status'] ?? 'unpaid',
        $invoice,
        $_POST['final_status'] ?? '',
        $_POST['solution_description'] ?? '',
        $_POST['technician_notes'] ?? '',
        $_POST['warranty_months'] ?? null,
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
                if (empty($part['part_id'])) continue;
                $qty = $part['quantity'] ?? 1;
                $partStmt->execute([
                    $repairId,
                    $part['part_id'],
                    $qty,
                    $part['unit_price'] ?? 0,
                    $part['purchase_price'] ?? 0
                ]);
                // کاهش موجودی
                $updateStock->execute([$qty, $part['part_id']]);
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