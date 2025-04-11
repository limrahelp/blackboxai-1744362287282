<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            checkAuth();
            
            // Get filter parameters
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $productId = $_GET['product_id'] ?? null;
            $paymentMethod = $_GET['payment_method'] ?? null;

            // Base query for orders
            $query = "
                SELECT 
                    o.id,
                    o.bill_number,
                    o.created_at,
                    o.tax_rate,
                    o.discount,
                    o.payment_method,
                    t.name as table_name,
                    GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as items,
                    SUM(oi.quantity * oi.price) as subtotal,
                    SUM(oi.quantity * oi.price * (o.tax_rate/100)) as tax_amount,
                    SUM(oi.quantity * oi.price * (1 + o.tax_rate/100) * (1 - o.discount/100)) as total
                FROM orders o
                JOIN tables t ON o.table_id = t.id
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                WHERE o.status = 'closed'
                AND DATE(o.created_at) BETWEEN :start_date AND :end_date
            ";

            $params = [
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ];

            if ($productId) {
                $query .= " AND oi.product_id = :product_id";
                $params[':product_id'] = $productId;
            }

            if ($paymentMethod) {
                $query .= " AND o.payment_method = :payment_method";
                $params[':payment_method'] = $paymentMethod;
            }

            $query .= " GROUP BY o.id ORDER BY o.created_at DESC";

            // Get orders
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate summary
            $totalSales = 0;
            $totalTax = 0;
            foreach ($orders as &$order) {
                $order['items'] = explode(', ', $order['items']);
                $order['subtotal'] = floatval($order['subtotal']);
                $order['tax_amount'] = floatval($order['tax_amount']);
                $order['total'] = floatval($order['total']);
                
                $totalSales += $order['total'];
                $totalTax += $order['tax_amount'];
            }

            // Get product-wise summary if no specific product is selected
            $productSummary = [];
            if (!$productId) {
                $query = "
                    SELECT 
                        p.name,
                        SUM(oi.quantity) as quantity,
                        SUM(oi.quantity * oi.price) as amount
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    JOIN products p ON oi.product_id = p.id
                    WHERE o.status = 'closed'
                    AND DATE(o.created_at) BETWEEN :start_date AND :end_date
                ";

                if ($paymentMethod) {
                    $query .= " AND o.payment_method = :payment_method";
                }

                $query .= " GROUP BY p.id ORDER BY amount DESC LIMIT 10";

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $productSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Get payment method summary if no specific method is selected
            $paymentSummary = [];
            if (!$paymentMethod) {
                $query = "
                    SELECT 
                        payment_method,
                        COUNT(*) as count,
                        SUM(
                            (SELECT SUM(quantity * price * (1 + o.tax_rate/100) * (1 - o.discount/100))
                            FROM order_items
                            WHERE order_id = o.id)
                        ) as amount
                    FROM orders o
                    WHERE status = 'closed'
                    AND DATE(created_at) BETWEEN :start_date AND :end_date
                ";

                if ($productId) {
                    $query .= " AND EXISTS (
                        SELECT 1 FROM order_items oi 
                        WHERE oi.order_id = o.id 
                        AND oi.product_id = :product_id
                    )";
                }

                $query .= " GROUP BY payment_method ORDER BY amount DESC";

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $paymentSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            echo json_encode([
                'success' => true,
                'summary' => [
                    'total_sales' => $totalSales,
                    'total_tax' => $totalTax,
                    'total_orders' => count($orders)
                ],
                'orders' => $orders,
                'product_summary' => $productSummary,
                'payment_summary' => $paymentSummary
            ]);
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
