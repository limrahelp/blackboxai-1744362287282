<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get all payment methods
            $stmt = $pdo->query("SELECT * FROM payment_methods ORDER BY name");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'POST':
            // Add new payment method
            checkAuth();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name'])) {
                throw new Exception('Payment method name is required');
            }

            $stmt = $pdo->prepare("INSERT INTO payment_methods (name) VALUES (?)");
            $stmt->execute([$data['name']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment method added successfully',
                'id' => $pdo->lastInsertId()
            ]);
            break;

        case 'PUT':
            // Update payment method
            checkAuth();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id']) || !isset($data['name'])) {
                throw new Exception('Payment method ID and name are required');
            }

            $stmt = $pdo->prepare("UPDATE payment_methods SET name = ? WHERE id = ?");
            $stmt->execute([$data['name'], $data['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment method updated successfully'
            ]);
            break;

        case 'DELETE':
            // Delete payment method
            checkAuth();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'])) {
                throw new Exception('Payment method ID is required');
            }

            // Check if payment method is used in any orders
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE payment_method = (SELECT name FROM payment_methods WHERE id = ?)");
            $stmt->execute([$data['id']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete payment method that is used in orders');
            }

            $stmt = $pdo->prepare("DELETE FROM payment_methods WHERE id = ?");
            $stmt->execute([$data['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment method deleted successfully'
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
