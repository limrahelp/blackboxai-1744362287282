<?php 
require_once 'includes/header.php';

// Get last cash closure
$stmt = $pdo->query("
    SELECT end_date 
    FROM cash_closures 
    ORDER BY end_date DESC 
    LIMIT 1
");
$lastClosure = $stmt->fetch();
$startDate = $lastClosure ? $lastClosure['end_date'] : '1970-01-01 00:00:00';
?>

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Close Cash</h1>
        <button 
            onclick="closeCash()"
            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
            Close Cash & Print Summary
        </button>
    </div>

    <div class="mb-6">
        <p class="text-gray-600">
            Showing all transactions since: 
            <span class="font-semibold">
                <?php echo date('Y-m-d H:i:s', strtotime($startDate)); ?>
            </span>
        </p>
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

    <!-- Payment Method Summary -->
    <div class="mb-6">
        <h2 class="text-xl font-bold mb-4">Payment Methods</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    </tr>
                </thead>
                <tbody id="paymentSummaryBody">
                    <!-- Payment summary will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Product Summary -->
    <div>
        <h2 class="text-xl font-bold mb-4">Products Sold</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    </tr>
                </thead>
                <tbody id="productSummaryBody">
                    <!-- Product summary will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Print Modal -->
<div id="printModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-lg font-bold mb-4">Print Summary</h3>
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
            <button onclick="printAndClose()" 
                    class="bg-red-500 text-white py-2 px-4 rounded hover:bg-red-600">
                Print & Close
            </button>
        </div>
    </div>
</div>

<script>
// Load summary data
function loadSummary() {
    fetch('api/cash_closure.php')
        .then(response => response.json())
        .then(data => {
            // Update summary cards
            document.getElementById('totalSales').textContent = `₹${data.summary.total_sales.toFixed(2)}`;
            document.getElementById('totalTax').textContent = `₹${data.summary.total_tax.toFixed(2)}`;
            document.getElementById('totalOrders').textContent = data.summary.total_orders;

            // Update payment methods summary
            const paymentBody = document.getElementById('paymentSummaryBody');
            paymentBody.innerHTML = data.payment_summary.map(payment => `
                <tr class="border-b">
                    <td class="px-6 py-4">${payment.method}</td>
                    <td class="px-6 py-4 text-right">${payment.orders}</td>
                    <td class="px-6 py-4 text-right">₹${payment.amount.toFixed(2)}</td>
                </tr>
            `).join('');

            // Update products summary
            const productBody = document.getElementById('productSummaryBody');
            productBody.innerHTML = data.product_summary.map(product => `
                <tr class="border-b">
                    <td class="px-6 py-4">${product.name}</td>
                    <td class="px-6 py-4 text-right">${product.quantity}</td>
                    <td class="px-6 py-4 text-right">₹${product.amount.toFixed(2)}</td>
                </tr>
            `).join('');
        })
        .catch(error => {
            console.error('Error loading summary:', error);
            alert('Error loading summary. Please try again.');
        });
}

// Show print modal
function closeCash() {
    document.getElementById('printModal').classList.remove('hidden');
}

// Print summary and close cash
function printAndClose() {
    const copies = parseInt(document.getElementById('copyCount').value);
    
    fetch('api/cash_closure.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ copies })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            document.getElementById('printModal').classList.add('hidden');
            // Reload the page to show new summary
            location.reload();
        } else {
            throw new Error(result.message);
        }
    })
    .catch(error => {
        console.error('Error closing cash:', error);
        alert('Error closing cash. Please try again.');
    });
}

// Initial load
loadSummary();
</script>

<?php require_once 'includes/footer.php'; ?>
