<?php
require_once 'config.php';
checkAuth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant POS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <nav class="bg-gray-800 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="text-xl font-bold">Restaurant POS</div>
            <div class="space-x-4">
                <a href="/pos/index.php" class="hover:text-gray-300">POS</a>
                <a href="/pos/admin/products.php" class="hover:text-gray-300">Products</a>
                <a href="/pos/admin/tables.php" class="hover:text-gray-300">Tables</a>
                <a href="/pos/admin/reports.php" class="hover:text-gray-300">Reports</a>
                <a href="/pos/admin/settings.php" class="hover:text-gray-300">Settings</a>
                <a href="/pos/logout.php" class="hover:text-gray-300">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto p-4">
