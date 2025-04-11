<?php
require_once 'includes/config.php';

try {
    // Read and execute schema.sql
    $schema = file_get_contents('database/schema.sql');
    
    // Split the schema into individual queries
    $queries = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($queries as $query) {
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }

    // Create sample data
    
    // Add sample tables
    $tables = [
        ['name' => '1'],
        ['name' => '2'],
        ['name' => '3'],
        ['name' => '4'],
        ['name' => '5'],
        ['name' => '6']
    ];

    $stmt = $pdo->prepare("INSERT INTO tables (name) VALUES (:name)");
    foreach ($tables as $table) {
        $stmt->execute($table);
    }

    // Add sample products
    $products = [
        ['name' => 'Chicken Burger', 'short_code' => 'CB', 'price' => 199.00],
        ['name' => 'Veg Burger', 'short_code' => 'VB', 'price' => 149.00],
        ['name' => 'French Fries', 'short_code' => 'FF', 'price' => 99.00],
        ['name' => 'Coca Cola', 'short_code' => 'CC', 'price' => 49.00],
        ['name' => 'Ice Cream', 'short_code' => 'IC', 'price' => 79.00],
        ['name' => 'Pizza', 'short_code' => 'PZ', 'price' => 299.00],
        ['name' => 'Pasta', 'short_code' => 'PA', 'price' => 199.00],
        ['name' => 'Sandwich', 'short_code' => 'SW', 'price' => 149.00],
        ['name' => 'Coffee', 'short_code' => 'CF', 'price' => 79.00],
        ['name' => 'Tea', 'short_code' => 'TE', 'price' => 49.00]
    ];

    $stmt = $pdo->prepare("INSERT INTO products (name, short_code, price) VALUES (:name, :short_code, :price)");
    foreach ($products as $product) {
        $stmt->execute($product);
    }

    // Add default settings
    $settings = [
        ['key' => 'store_name', 'value' => 'My Restaurant'],
        ['key' => 'address', 'value' => '123 Restaurant Street'],
        ['key' => 'tax_number', 'value' => 'GST123456789'],
        ['key' => 'phone', 'value' => '+1234567890'],
        ['key' => 'footer_text', 'value' => 'Thank you for dining with us!']
    ];

    $stmt = $pdo->prepare("INSERT INTO settings (`key`, value) VALUES (:key, :value)");
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }

    // Success message
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>POS System Setup</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full">
            <h1 class="text-2xl font-bold text-green-600 mb-4">Setup Completed Successfully!</h1>
            
            <div class="mb-6">
                <p class="text-gray-600 mb-4">
                    The POS system has been set up successfully with sample data. You can now log in with the following credentials:
                </p>
                
                <div class="bg-gray-50 p-4 rounded">
                    <p class="mb-2"><strong>Username:</strong> admin</p>
                    <p><strong>Password:</strong> admin123</p>
                </div>
            </div>

            <div class="mb-6">
                <p class="text-gray-600">
                    Please make sure to change the admin password after your first login.
                </p>
            </div>

            <a href="login.php" 
               class="block w-full bg-blue-500 text-white text-center py-2 px-4 rounded hover:bg-blue-600">
                Go to Login Page
            </a>
        </div>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    // Error message
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>POS System Setup Error</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full">
            <h1 class="text-2xl font-bold text-red-600 mb-4">Setup Failed</h1>
            
            <div class="mb-6">
                <p class="text-gray-600 mb-4">
                    An error occurred during the setup process:
                </p>
                
                <div class="bg-red-50 text-red-700 p-4 rounded">
                    <?php echo htmlspecialchars($e->getMessage()); ?>
                </div>
            </div>

            <p class="text-gray-600 mb-6">
                Please check the error message and try again. If the problem persists, contact technical support.
            </p>

            <button onclick="window.location.reload()" 
                    class="block w-full bg-blue-500 text-white text-center py-2 px-4 rounded hover:bg-blue-600">
                Try Again
            </button>
        </div>
    </body>
    </html>
    <?php
}
?>
