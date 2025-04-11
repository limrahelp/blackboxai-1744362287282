<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get current order for a table
            if (!isset($_GET['table_id'])) {
                throw new Exception('Table ID is required');
            }

            $stmt = $pdo->prepare("
                SELECT 
                    o.id,
                    o.table_id,
                    o.tax_rate,
                    o.discount,
                    o.payment_method,
                    oi.product_id,
                    p.name,
                    p.price,
                    oi.quantity
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE o.table_id = ? AND o.status = 'open'
            ");
            $stmt->execute([$_GET['table_id']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                echo json_encode(null);
                exit;
            }

            // Format response
            $order = [
                'id' => $rows[0]['id'],
                'table_id' => $rows[0]['table_id'],
                'tax' => $rows[0]['tax_rate'],
                'discount' => $rows[0]['discount'],
                'payment_method' => $rows[0]['payment_method'],
                'items' => []
            ];

            foreach ($rows as $row) {
                if ($row['product_id']) {
                    $order['items'][] = [
                        'product_id' => $row['product_id'],
                        'name' => $row['name'],
                        'price' => $row['price'],
                        'quantity' => $row['quantity']
                    ];
                }
            }

            echo json_encode($order);
            break;

        case 'POST':
            // Create or update order
            checkAuth();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['table_id']) || !isset($data['order'])) {
                throw new Exception('Table ID and order details are required');
            }

            $pdo->beginTransaction();

            try {
                // Check if there's an existing open order
                $stmt = $pdo->prepare("
                    SELECT id FROM orders 
                    WHERE table_id = ? AND status = 'open'
                ");
                $stmt->execute([$data['table_id']]);
                $existingOrder = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingOrder) {
                    // Update existing order
                    $orderId = $existingOrder['id'];
                    
                    // Delete existing items
                    $stmt = $pdo->prepare("
                        DELETE FROM order_items 
                        WHERE order_id = ?
                    ");
                    $stmt->execute([$orderId]);

                    // Update order details
                    $stmt = $pdo->prepare("
                        UPDATE orders 
                        SET tax_rate = ?,
                            discount = ?,
                            payment_method = ?,
                            status = 'closed',
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $data['order']['tax'],
                        $data['order']['discount'],
                        $data['order']['paymentMethod'],
                        $orderId
                    ]);
                } else {
                    // Create new order
                    $stmt = $pdo->prepare("
                        INSERT INTO orders (
                            table_id, 
                            bill_number,
                            tax_rate,
                            discount,
                            payment_method,
                            status,
                            user_id,
                            created_at,
                            updated_at
                        ) VALUES (?, 
                            (SELECT COALESCE(MAX(bill_number), 0) + 1 FROM orders),
                            ?, ?, ?, 'closed', ?, NOW(), NOW()
                        )
                    ");
                    $stmt->execute([
                        $data['table_id'],
                        $data['order']['tax'],
                        $data['order']['discount'],
                        $data['order']['paymentMethod'],
                        $_SESSION['user_id']
                    ]);
                    $orderId = $pdo->lastInsertId();
                }

                // Insert order items
                if (!empty($data['order']['items'])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (
                            order_id,
                            product_id,
                            quantity,
                            price
                        ) VALUES (?, ?, ?, ?)
                    ");

                    foreach ($data['order']['items'] as $item) {
                        $stmt->execute([
                            $orderId,
                            $item['product_id'],
                            $item['quantity'],
                            $item['price']
                        ]);
                    }
                }

                // Generate print data
                $printData = generatePrintData($pdo, $orderId, $data['copies'] ?? 1);

                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Order processed successfully',
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

function generatePrintData($pdo, $orderId, $copies) {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            t.name as table_name,
            u.username as server_name,
            s.value as store_name
        FROM orders o
        JOIN tables t ON o.table_id = t.id
        JOIN users u ON o.user_id = u.id
        LEFT JOIN settings s ON s.key = 'store_name'
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get order items
    $stmt = $pdo->prepare("
        SELECT 
            oi.*,
            p.name as product_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $tax = $subtotal * ($order['tax_rate'] / 100);
    $discount = $subtotal * ($order['discount'] / 100);
    $total = $subtotal + $tax - $discount;

    // Format print data
    $printData = [
        'copies' => $copies,
        'header' => [
            'store_name' => $order['store_name'] ?? 'Restaurant POS',
            'bill_number' => $order['bill_number'],
            'date' => date('Y-m-d H:i:s', strtotime($order['created_at'])),
            'table' => $order['table_name'],
            'server' => $order['server_name']
        ],
        'items' => array_map(function($item) {
            return [
                'name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['price'] * $item['quantity']
            ];
        }, $items),
        'summary' => [
            'subtotal' => $subtotal,
            'tax' => [
                'rate' => $order['tax_rate'],
                'amount' => $tax
            ],
            'discount' => [
                'rate' => $order['discount'],
                'amount' => $discount
            ],
            'total' => $total,
            'payment_method' => $order['payment_method']
        ]
    ];

    return $printData;
}
?>
