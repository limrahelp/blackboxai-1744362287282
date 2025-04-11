<?php 
require_once '../includes/header.php';

// Get payment methods for filter
$stmt = $pdo->query("SELECT DISTINCT payment_method FROM orders WHERE payment_method IS NOT NULL ORDER BY payment_method");
$payment_methods = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get products for filter
$stmt = $pdo->query("SELECT id, name FROM products ORDER BY name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-white rounded-lg shadow p-6">
    <h1 class="text-2xl font-bold mb-6">Sales Reports</h1>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="startDate">
                Start Date
            </label>
            <input 
                type="date" 
                id="startDate" 
                class="w-full p-2 border rounded"
                value="<?php echo date('Y-m-01'); ?>">
        </div>
        
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="endDate">
                End Date
            </label>
            <input 
                type="date" 
                id="endDate" 
                class="w-full p-2 border rounded"
                value="<?php echo date('Y-m-d'); ?>">
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="product">
                Product
            </label>
            <select id="product" class="w-full p-2 border rounded">
                <option value="">All Products</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['id']; ?>">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="paymentMethod">
                Payment Method
            </label>
            <select id="paymentMethod" class="w-full p-2 border rounded">
                <option value="">All Methods</option>
                <?php foreach ($payment_methods as $method): ?>
                    <option value="<?php echo htmlspecialchars($method); ?>">
                        <?php echo htmlspecialchars($method); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="flex justify-end mb-6">
        <button 
            onclick="generateReport()"
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Generate Report
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold mb-2">Total Sales</h3>
            <p class="text-2xl" id="totalSales">₹0.00</p>
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold mb-2">Total Tax</h3>
            <p class="text-2xl" id="totalTax">₹0.00</p>
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold mb-2">Total Orders</h3>
            <p class="text-2xl" id="totalOrders">0</p>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Table</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tax</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody id="salesTableBody">
                <!-- Sales data will be loaded here -->
            </tbody>
        </table>
    </div>
</div>

<!-- Print Modal -->
<div id="printModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-lg font-bold mb-4">Print Bill</h3>
        <div class="mb-4">
            <label class="block mb-2">Number of copies:</label>
            <input type="number" 
                   id="copyCount" 
                   class="border rounded p-2 w-full" 
                   value="1" 
                   min="1" 
                   max="5">
        </div>
        <div class="flex justify-end space-x-2">
            <button onclick="document.getElementById('printModal').classList.add('hidden')" 
                    class="bg-gray-300 text-black py-2 px-4 rounded hover:bg-gray-400">
                Cancel
            </button>
            <button onclick="printBill()" 
                    class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">
                Print
            </button>
        </div>
    </div>
</div>

<script>
let currentBillId = null;

function generateReport() {
    const filters = {
        start_date: document.getElementById('startDate').value,
        end_date: document.getElementById('endDate').value,
        product_id: document.getElementById('product').value,
        payment_method: document.getElementById('paymentMethod').value
    };

    const queryString = Object.entries(filters)
        .filter(([_, value]) => value)
        .map(([key, value]) => `${key}=${encodeURIComponent(value)}`)
        .join('&');

    fetch(`../api/reports.php?${queryString}`)
        .then(response => response.json())
        .then(data => {
            // Update summary cards
            document.getElementById('totalSales').textContent = `₹${data.summary.total_sales.toFixed(2)}`;
            document.getElementById('totalTax').textContent = `₹${data.summary.total_tax.toFixed(2)}`;
            document.getElementById('totalOrders').textContent = data.summary.total_orders;

            // Update sales table
            const tbody = document.getElementById('salesTableBody');
            tbody.innerHTML = data.orders.map(order => `
                <tr class="border-b">
                    <td class="px-6 py-4">${new Date(order.created_at).toLocaleDateString()}</td>
                    <td class="px-6 py-4">${order.bill_number}</td>
                    <td class="px-6 py-4">Table ${order.table_name}</td>
                    <td class="px-6 py-4">${order.items.join(', ')}</td>
                    <td class="px-6 py-4">${order.payment_method}</td>
                    <td class="px-6 py-4 text-right">₹${order.subtotal.toFixed(2)}</td>
                    <td class="px-6 py-4 text-right">₹${order.tax_amount.toFixed(2)}</td>
                    <td class="px-6 py-4 text-right">₹${order.total.toFixed(2)}</td>
                    <td class="px-6 py-4 text-center">
                        <button 
                            onclick="showPrintModal('${order.id}')"
                            class="text-blue-500 hover:text-blue-700">
                            <i class="fas fa-print"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        })
        .catch(error => {
            console.error('Error generating report:', error);
            alert('Error generating report. Please try again.');
        });
}

function showPrintModal(billId) {
    currentBillId = billId;
    document.getElementById('printModal').classList.remove('hidden');
}

function printBill() {
    const copies = parseInt(document.getElementById('copyCount').value);
    
    fetch('../api/orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            order_id: currentBillId,
            copies: copies,
            reprint: true
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            document.getElementById('printModal').classList.add('hidden');
        } else {
            throw new Error(result.message);
        }
    })
    .catch(error => {
        console.error('Error printing bill:', error);
        alert('Error printing bill. Please try again.');
    });
}

// Initial report generation
generateReport();
</script>

<?php require_once '../includes/footer.php'; ?>
