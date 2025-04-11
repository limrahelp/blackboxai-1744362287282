<?php require_once 'includes/header.php'; ?>

<div class="grid grid-cols-12 gap-4 h-[calc(100vh-8rem)]">
    <!-- Section 1: Table Selection -->
    <div class="col-span-2 bg-white rounded-lg shadow p-4 overflow-y-auto" role="complementary" aria-label="Table Selection">
        <h2 class="text-xl font-bold mb-4">Tables</h2>
        <div class="grid grid-cols-2 gap-2" id="tableGrid">
            <!-- Tables will be dynamically loaded here -->
        </div>
    </div>

    <!-- Section 2: Product Search and Selection -->
    <div class="col-span-6 bg-white rounded-lg shadow p-4 overflow-y-auto">
        <div class="mb-4">
            <input type="text" 
                   id="productSearch" 
                   class="w-full p-2 border rounded" 
                   placeholder="Search products by name or code...">
        </div>
        <div class="grid grid-cols-3 gap-2" id="productGrid">
            <!-- Products will be dynamically loaded here -->
        </div>
    </div>

    <!-- Section 3: Bill Preview -->
    <div class="col-span-4 bg-white rounded-lg shadow p-4 overflow-y-auto">
        <h2 class="text-xl font-bold mb-4">Current Bill</h2>
        <div id="selectedTable" class="text-lg mb-4">No table selected</div>
        
        <div class="mb-4 overflow-y-auto max-h-[50vh]">
            <table class="w-full" id="billTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-2 text-left">Item</th>
                        <th class="p-2 text-right">Qty</th>
                        <th class="p-2 text-right">Price</th>
                        <th class="p-2 text-right">Total</th>
                        <th class="p-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Bill items will be dynamically loaded here -->
                </tbody>
            </table>
        </div>

        <div class="border-t pt-4">
            <div class="flex justify-between mb-2">
                <span>Subtotal:</span>
                <span id="subtotal">₹0.00</span>
            </div>
            <div class="flex justify-between mb-2">
                <span>Tax:</span>
                <select id="taxRate" class="border rounded px-2">
                    <option value="0">No Tax</option>
                    <option value="5">GST 5%</option>
                    <option value="18">GST 18%</option>
                </select>
                <span id="taxAmount">₹0.00</span>
            </div>
            <div class="flex justify-between mb-2">
                <span>Discount:</span>
                <input type="number" 
                       id="discountPercent" 
                       class="border rounded w-20 px-2" 
                       value="0" 
                       min="0" 
                       max="100">
                <span id="discountAmount">₹0.00</span>
            </div>
            <div class="flex justify-between mb-4 text-xl font-bold">
                <span>Total:</span>
                <span id="totalAmount">₹0.00</span>
            </div>

            <div class="space-y-2">
                <select id="paymentMethod" class="w-full p-2 border rounded mb-2">
                    <option value="">Select Payment Method</option>
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="upi">UPI</option>
                </select>
                <button id="settleBill" 
                        class="w-full bg-green-500 text-white py-2 px-4 rounded hover:bg-green-600 disabled:bg-gray-400"
                        disabled>
                    Settle Bill & Close Table
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Print Preview Modal -->
<div id="printModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-4 rounded-lg w-96">
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
let currentTable = null;
let currentBill = {
    items: [],
    tax: 0,
    discount: 0,
    paymentMethod: ''
};

function handleError(error) {
    console.error('An error occurred:', error);
    alert('An error occurred while loading data. Please try again later.');
}

// Load tables
function loadTables() {
    fetch('api/tables.php')
        .then(response => response.json())
        .then(tables => {
            const tableGrid = document.getElementById('tableGrid');
            tableGrid.innerHTML = tables.map(table => `
                <button 
                    class="p-4 rounded ${table.status === 'open' ? 'bg-yellow-200' : 'bg-gray-200'} hover:opacity-80"
                    onclick="selectTable(${table.id})"
                >
                    Table ${table.name}
                </button>
            `).join('');
        })
        .catch(handleError);
}

// Load products
function loadProducts(search = '') {
    fetch(`api/products.php?search=${encodeURIComponent(search)}`)
        .then(response => response.json())
        .then(products => {
            const productGrid = document.getElementById('productGrid');
            productGrid.innerHTML = products.map(product => `
                <button 
                    class="p-4 rounded bg-gray-100 hover:bg-gray-200 text-left"
                    onclick="addProduct(${JSON.stringify(product).replace(/"/g, '"')})"
                >
                    <div class="font-bold">${product.name}</div>
                    <div class="text-sm text-gray-600">${product.short_code}</div>
                    <div class="text-right">₹${product.price}</div>
                </button>
            `).join('');
        })
        .catch(handleError);
}

// Select table
function selectTable(tableId) {
    if (currentTable === tableId) return;
    
    fetch(`api/orders.php?table_id=${tableId}`)
        .then(response => response.json())
        .then(order => {
            currentTable = tableId;
            currentBill = order || {
                items: [],
                tax: 0,
                discount: 0,
                paymentMethod: ''
            };
            document.getElementById('selectedTable').textContent = `Table ${tableId}`;
            document.getElementById('settleBill').disabled = false;
            updateBillDisplay();
        })
        .catch(error => console.error('Error loading order:', error));
}

// Add product to bill
function addProduct(product) {
    if (!currentTable) {
        alert('Please select a table first');
        return;
    }

    const existingItem = currentBill.items.find(item => item.product_id === product.id);
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        currentBill.items.push({
            product_id: product.id,
            name: product.name,
            price: product.price,
            quantity: 1
        });
    }
    
    updateBillDisplay();
}

// Update bill display
function updateBillDisplay() {
    const billTable = document.getElementById('billTable').getElementsByTagName('tbody')[0];
    billTable.innerHTML = currentBill.items.map((item, index) => `
        <tr>
            <td class="p-2">${item.name}</td>
            <td class="p-2 text-right">
                <input type="number" 
                       value="${item.quantity}" 
                       min="1" 
                       class="w-16 border rounded text-right"
                       onchange="updateQuantity(${index}, this.value)">
            </td>
            <td class="p-2 text-right">₹${item.price}</td>
            <td class="p-2 text-right">₹${(item.price * item.quantity).toFixed(2)}</td>
            <td class="p-2 text-right">
                <button onclick="removeItem(${index})" 
                        class="text-red-500 hover:text-red-700">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>
    `).join('');

    calculateTotals();
}

// Calculate totals
function calculateTotals() {
    const subtotal = currentBill.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const taxRate = parseFloat(document.getElementById('taxRate').value) / 100;
    const discountPercent = parseFloat(document.getElementById('discountPercent').value) / 100;
    
    const taxAmount = subtotal * taxRate;
    const discountAmount = subtotal * discountPercent;
    const total = subtotal + taxAmount - discountAmount;

    document.getElementById('subtotal').textContent = `₹${subtotal.toFixed(2)}`;
    document.getElementById('taxAmount').textContent = `₹${taxAmount.toFixed(2)}`;
    document.getElementById('discountAmount').textContent = `₹${discountAmount.toFixed(2)}`;
    document.getElementById('totalAmount').textContent = `₹${total.toFixed(2)}`;
}

// Update quantity
function updateQuantity(index, quantity) {
    currentBill.items[index].quantity = parseInt(quantity);
    updateBillDisplay();
}

// Remove item
function removeItem(index) {
    currentBill.items.splice(index, 1);
    updateBillDisplay();
}

// Settle bill
document.getElementById('settleBill').addEventListener('click', function() {
    const paymentMethod = document.getElementById('paymentMethod').value;
    if (!paymentMethod) {
        alert('Please select a payment method');
        return;
    }

    currentBill.paymentMethod = paymentMethod;
    currentBill.tax = parseFloat(document.getElementById('taxRate').value);
    currentBill.discount = parseFloat(document.getElementById('discountPercent').value);

    // Show print modal
    document.getElementById('printModal').classList.remove('hidden');
});

// Print bill
function printBill() {
    const copies = parseInt(document.getElementById('copyCount').value);
    
    fetch('api/orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            table_id: currentTable,
            order: currentBill,
            copies: copies
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Reset current bill
            currentTable = null;
            currentBill = {
                items: [],
                tax: 0,
                discount: 0,
                paymentMethod: ''
            };
            document.getElementById('selectedTable').textContent = 'No table selected';
            document.getElementById('settleBill').disabled = true;
            document.getElementById('printModal').classList.add('hidden');
            updateBillDisplay();
            loadTables(); // Refresh tables
        } else {
            throw new Error(result.message);
        }
    })
    .catch(error => {
        console.error('Error settling bill:', error);
        alert('Error settling bill. Please try again.');
    });
}

// Event listeners
let debounceTimer;
document.getElementById('productSearch').addEventListener('input', function(e) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        loadProducts(e.target.value);
    }, 300); // Adjust the delay as needed
});

document.getElementById('taxRate').addEventListener('change', calculateTotals);
document.getElementById('discountPercent').addEventListener('input', calculateTotals);

// Initial load
loadTables();
loadProducts();
</script>

<?php require_once 'includes/footer.php'; ?>
