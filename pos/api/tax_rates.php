<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get all tax rates
            $stmt = $pdo->query("SELECT * FROM tax_rates ORDER BY rate");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'POST':
            // Add new tax rate
            checkAuth();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name']) || !isset($data['rate'])) {
                throw new Exception('Tax name and rate are required');
            }

            if (!is_numeric($data['rate']) || $data['rate'] < 0 || $data['rate'] > 100) {
                throw new Exception('Invalid tax rate');
            }

            $stmt = $pdo->prepare("INSERT INTO tax_rates (name, rate) VALUES (?, ?)");
            $stmt->execute([$data['name'], $data['rate']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Tax rate added successfully',
                'id' => $pdo->lastInsertId()
            ]);
            break;

        case 'PUT':
            // Update tax rate
            checkAuth();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id']) || !isset($data['name']) || !isset($data['rate'])) {
                throw new Exception('Tax ID, name, and rate are required');
            }

            if (!is_numeric($data['rate']) || $data['rate'] < 0 || $data['rate'] > 100) {
                throw new Exception('Invalid tax rate');
            }

            $stmt = $pdo->prepare("UPDATE tax_rates SET name = ?, rate = ? WHERE id = ?");
            $stmt->execute([$data['name'], $data['rate'], $data['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Tax rate updated successfully'
            ]);
            break;

        case 'DELETE':
            // Delete tax rate
            checkAuth();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'])) {
                throw new Exception('Tax ID is required');
            }

            // Check if tax rate is used in any orders
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE tax_rate = (SELECT rate FROM tax_rates WHERE id = ?)");
            $stmt->execute([$data['id']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete tax rate that is used in orders');
            }

            $stmt = $pdo->prepare("DELETE FROM tax_rates WHERE id = ?");
            $stmt->execute([$data['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Tax rate deleted successfully'
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
