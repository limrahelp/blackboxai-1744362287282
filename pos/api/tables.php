<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get all tables with their status
            $stmt = $pdo->query("
                SELECT 
                    t.id,
                    t.name,
                    CASE WHEN o.id IS NOT NULL THEN 'open' ELSE 'available' END as status
                FROM tables t
                LEFT JOIN orders o ON t.id = o.table_id AND o.status = 'open'
                ORDER BY t.name
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'POST':
            // Add new table
            checkAuth(); // Ensure user is authenticated
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name'])) {
                throw new Exception('Table name is required');
            }

            $stmt = $pdo->prepare("INSERT INTO tables (name) VALUES (?)");
            $stmt->execute([$data['name']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Table added successfully',
                'id' => $pdo->lastInsertId()
            ]);
            break;

        case 'PUT':
            // Update table
            checkAuth();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id']) || !isset($data['name'])) {
                throw new Exception('Table ID and name are required');
            }

            $stmt = $pdo->prepare("UPDATE tables SET name = ? WHERE id = ?");
            $stmt->execute([$data['name'], $data['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Table updated successfully'
            ]);
            break;

        case 'DELETE':
            // Delete table
            checkAuth();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'])) {
                throw new Exception('Table ID is required');
            }

            // Check if table has any orders
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE table_id = ?");
            $stmt->execute([$data['id']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete table with existing orders');
            }

            $stmt = $pdo->prepare("DELETE FROM tables WHERE id = ?");
            $stmt->execute([$data['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Table deleted successfully'
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
