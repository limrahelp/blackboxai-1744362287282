<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get all products or search products
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            
            if ($search) {
                $stmt = $pdo->prepare("
                    SELECT id, name, short_code, price
                    FROM products 
                    WHERE name LIKE ? OR short_code LIKE ?
                    ORDER BY name
                ");
                $searchTerm = "%{$search}%";
                $stmt->execute([$searchTerm, $searchTerm]);
            } else {
                $stmt = $pdo->query("
                    SELECT id, name, short_code, price
                    FROM products 
                    ORDER BY name
                ");
            }
            
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'POST':
            // Add new product
            checkAuth();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name']) || !isset($data['price'])) {
                throw new Exception('Product name and price are required');
            }

            // Validate price
            if (!is_numeric($data['price']) || $data['price'] < 0) {
                throw new Exception('Invalid price');
            }

            // Generate short code if not provided
            if (!isset($data['short_code'])) {
                $data['short_code'] = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $data['name']), 0, 4));
            }

            $stmt = $pdo->prepare("
                INSERT INTO products (name, short_code, price) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $data['name'],
                $data['short_code'],
                $data['price']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Product added successfully',
                'id' => $pdo->lastInsertId()
            ]);
            break;

        case 'PUT':
            // Update product
            checkAuth();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id']) || !isset($data['name']) || !isset($data['price'])) {
                throw new Exception('Product ID, name, and price are required');
            }

            // Validate price
            if (!is_numeric($data['price']) || $data['price'] < 0) {
                throw new Exception('Invalid price');
            }

            $stmt = $pdo->prepare("
                UPDATE products 
                SET name = ?, 
                    short_code = ?,
                    price = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['name'],
                $data['short_code'],
                $data['price'],
                $data['id']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Product updated successfully'
            ]);
            break;

        case 'DELETE':
            // Delete product
            checkAuth();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'])) {
                throw new Exception('Product ID is required');
            }

            // Check if product is used in any orders
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM order_items 
                WHERE product_id = ?
            ");
            $stmt->execute([$data['id']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete product with existing orders');
            }

            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$data['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Product deleted successfully'
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
