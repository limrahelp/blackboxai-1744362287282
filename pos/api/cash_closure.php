<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get summary since last closure
            checkAuth();

            // Get last closure date
            $stmt = $pdo->query("
                SELECT end_date 
                FROM cash_closures 
                ORDER BY end_date DESC 
                LIMIT 1
            ");
            $lastClosure = $stmt->fetch();
            $startDate = $lastClosure ? $lastClosure['end_date'] : '1970-01-01 00:00:00';

            // Get orders summary
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(
                        (SELECT SUM(quantity * price * (1 + o.tax_rate/100) * (1 - o.discount/100))
                        FROM order_items
                        WHERE order_id = o.id)
                    ) as total_sales,
                    SUM(
                        (SELECT SUM(quantity * price * (o.tax_rate/100))
                        FROM order_items
                        WHERE order_id = o.id)
                    ) as total_tax
                FROM orders o
                WHERE status = 'closed'
                AND created_at > :start_date
            ");
            $stmt->execute([':start_date' => $startDate]);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get payment methods summary
            $stmt = $pdo->prepare("
                SELECT 
                    payment_method as method,
                    COUNT(*) as orders,
                    SUM(
                        (SELECT SUM(quantity * price * (1 + o.tax_rate/100) * (1 - o.discount/100))
                        FROM order_items
                        WHERE order_id = o.id)
                    ) as amount
                FROM orders o
                WHERE status = 'closed'
                AND created_at > :start_date
                GROUP BY payment_method
                ORDER BY amount DESC
            ");
            $stmt->execute([':start_date' => $startDate]);
            $paymentSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get products summary
            $stmt = $pdo->prepare("
                SELECT 
                    p.name,
                    SUM(oi.quantity) as quantity,
                    SUM(oi.quantity * oi.price * (1 + o.tax_rate/100) * (1 - o.discount/100)) as amount
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                WHERE o.status = 'closed'
                AND o.created_at > :start_date
                GROUP BY p.id
                ORDER BY amount DESC
            ");
            $stmt->execute([':start_date' => $startDate]);
            $productSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'summary' => [
                    'total_orders' => (int)$summary['total_orders'],
                    'total_sales' => (float)$summary['total_sales'],
                    'total_tax' => (float)$summary['total_tax']
                ],
                'payment_summary' => array_map(function($row) {
                    return [
                        'method' => $row['method'],
                        'orders' => (int)$row['orders'],
                        'amount' => (float)$row['amount']
                    ];
                }, $paymentSummary),
                'product_summary' => array_map(function($row) {
                    return [
                        'name' => $row['name'],
                        'quantity' => (int)$row['quantity'],
                        'amount' => (float)$row['amount']
                    ];
                }, $productSummary)
            ]);
            break;

        case 'POST':
            // Close cash and reset bill numbers
            checkAuth();
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Get last closure date
            $stmt = $pdo->query("
                SELECT end_date 
                FROM cash_closures 
                ORDER BY end_date DESC 
                LIMIT 1
            ");
            $lastClosure = $stmt->fetch();
            $startDate = $lastClosure ? $lastClosure['end_date'] : '1970-01-01 00:00:00';

            $pdo->beginTransaction();

            try {
                // Get summary for the period
                $stmt = $pdo->prepare("
                    SELECT 
                        SUM(
                            (SELECT SUM(quantity * price * (1 + o.tax_rate/100) * (1 - o.discount/100))
                            FROM order_items
                            WHERE order_id = o.id)
                        ) as total_sales,
                        SUM(
                            (SELECT SUM(quantity * price * (o.tax_rate/100))
                            FROM order_items
                            WHERE order_id = o.id)
                        ) as total_tax
                    FROM orders o
                    WHERE status = 'closed'
                    AND created_at > :start_date
                ");
                $stmt->execute([':start_date' => $startDate]);
                $summary = $stmt->fetch(PDO::FETCH_ASSOC);

                // Create cash closure record
                $stmt = $pdo->prepare("
                    INSERT INTO cash_closures (
                        user_id,
                        start_date,
                        end_date,
                        total_sales,
                        total_tax
                    ) VALUES (?, ?, NOW(), ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $startDate,
                    $summary['total_sales'],
                    $summary['total_tax']
                ]);

                // Reset bill numbers for new orders
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET bill_number = NULL 
                    WHERE status = 'open'
                ");
                $stmt->execute();

                $pdo->commit();

                // Generate print data
                $printData = [
                    'copies' => $data['copies'] ?? 1,
                    'header' => [
                        'title' => 'Cash Closure Summary',
                        'date' => date('Y-m-d H:i:s'),
                        'period' => [
                            'start' => $startDate,
                            'end' => date('Y-m-d H:i:s')
                        ]
                    ],
                    'summary' => [
                        'total_sales' => (float)$summary['total_sales'],
                        'total_tax' => (float)$summary['total_tax']
                    ]
                ];

                echo json_encode([
                    'success' => true,
                    'message' => 'Cash closed successfully',
                    'print_data' => $printData
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
