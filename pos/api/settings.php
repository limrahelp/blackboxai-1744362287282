<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get all settings
            $stmt = $pdo->query("SELECT * FROM settings");
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['key']] = $row['value'];
            }
            echo json_encode($settings);
            break;

        case 'POST':
            // Update settings
            checkAuth();
            $data = json_decode(file_get_contents('php://input'), true);
            
            $pdo->beginTransaction();
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO settings (`key`, value) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE value = VALUES(value)
                ");

                foreach ($data as $key => $value) {
                    $stmt->execute([$key, $value]);
                }

                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Settings updated successfully'
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
