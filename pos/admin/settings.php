<?php 
require_once '../includes/header.php';

// Get current settings
$stmt = $pdo->query("SELECT * FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['key']] = $row['value'];
}

// Get tax rates
$stmt = $pdo->query("SELECT * FROM tax_rates ORDER BY rate");
$tax_rates = $stmt->fetchAll();

// Get payment methods
$stmt = $pdo->query("SELECT * FROM payment_methods ORDER BY name");
$payment_methods = $stmt->fetchAll();
?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Store Settings -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">Store Settings</h2>
        
        <form id="storeSettingsForm" onsubmit="saveStoreSettings(event)">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="storeName">
                    Store Name
                </label>
                <input 
                    type="text" 
                    id="storeName" 
                    class="w-full p-2 border rounded"
                    value="<?php echo htmlspecialchars($settings['store_name'] ?? ''); ?>"
                    required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="address">
                    Address
                </label>
                <textarea 
                    id="address" 
                    class="w-full p-2 border rounded" 
                    rows="3"
                    required><?php echo htmlspecialchars($settings['address'] ?? ''); ?></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="taxNumber">
                    Tax Identification Number
                </label>
                <input 
                    type="text" 
                    id="taxNumber" 
                    class="w-full p-2 border rounded"
                    value="<?php echo htmlspecialchars($settings['tax_number'] ?? ''); ?>"
                    required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                    Phone Number
                </label>
                <input 
                    type="text" 
                    id="phone" 
                    class="w-full p-2 border rounded"
                    value="<?php echo htmlspecialchars($settings['phone'] ?? ''); ?>"
                    required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="footerText">
                    Receipt Footer Text
                </label>
                <textarea 
                    id="footerText" 
                    class="w-full p-2 border rounded" 
                    rows="2"><?php echo htmlspecialchars($settings['footer_text'] ?? ''); ?></textarea>
            </div>

            <button 
                type="submit"
                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Save Store Settings
            </button>
        </form>
    </div>

    <!-- Tax Rates -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Tax Rates</h2>
            <button 
                onclick="openTaxModal()"
                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Add Tax Rate
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate (%)</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tax_rates as $tax): ?>
                    <tr class="border-b">
                        <td class="px-6 py-4"><?php echo htmlspecialchars($tax['name']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($tax['rate']); ?>%</td>
                        <td class="px-6 py-4 text-right">
                            <button 
                                onclick='editTax(<?php echo json_encode($tax); ?>)'
                                class="text-blue-500 hover:text-blue-700">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button 
                                onclick="deleteTax(<?php echo $tax['id']; ?>)"
                                class="text-red-500 hover:text-red-700 ml-2">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment Methods -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Payment Methods</h2>
            <button 
                onclick="openPaymentMethodModal()"
                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Add Payment Method
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payment_methods as $method): ?>
                    <tr class="border-b">
                        <td class="px-6 py-4"><?php echo htmlspecialchars($method['name']); ?></td>
                        <td class="px-6 py-4 text-right">
                            <button 
                                onclick='editPaymentMethod(<?php echo json_encode($method); ?>)'
                                class="text-blue-500 hover:text-blue-700">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button 
                                onclick="deletePaymentMethod(<?php echo $method['id']; ?>)"
                                class="text-red-500 hover:text-red-700 ml-2">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tax Rate Modal -->
<div id="taxModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 id="taxModalTitle" class="text-xl font-bold mb-4">Add Tax Rate</h2>
        
        <form id="taxForm" onsubmit="saveTax(event)">
            <input type="hidden" id="taxId">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="taxName">
                    Tax Name
                </label>
                <input 
                    type="text" 
                    id="taxName" 
                    class="w-full p-2 border rounded" 
                    required>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="taxRate">
                    Rate (%)
                </label>
                <input 
                    type="number" 
                    id="taxRate" 
                    class="w-full p-2 border rounded" 
                    step="0.01" 
                    min="0" 
                    max="100" 
                    required>
            </div>

            <div class="flex justify-end space-x-2">
                <button 
                    type="button"
                    onclick="closeTaxModal()"
                    class="bg-gray-300 text-black px-4 py-2 rounded hover:bg-gray-400">
                    Cancel
                </button>
                <button 
                    type="submit"
                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Payment Method Modal -->
<div id="paymentMethodModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 id="paymentMethodModalTitle" class="text-xl font-bold mb-4">Add Payment Method</h2>
        
        <form id="paymentMethodForm" onsubmit="savePaymentMethod(event)">
            <input type="hidden" id="paymentMethodId">
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="paymentMethodName">
                    Method Name
                </label>
                <input 
                    type="text" 
                    id="paymentMethodName" 
                    class="w-full p-2 border rounded" 
                    required>
            </div>

            <div class="flex justify-end space-x-2">
                <button 
                    type="button"
                    onclick="closePaymentMethodModal()"
                    class="bg-gray-300 text-black px-4 py-2 rounded hover:bg-gray-400">
                    Cancel
                </button>
                <button 
                    type="submit"
                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Store Settings
function saveStoreSettings(event) {
    event.preventDefault();

    const settings = {
        store_name: document.getElementById('storeName').value,
        address: document.getElementById('address').value,
        tax_number: document.getElementById('taxNumber').value,
        phone: document.getElementById('phone').value,
        footer_text: document.getElementById('footerText').value
    };

    fetch('../api/settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(settings)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Settings saved successfully');
        } else {
            throw new Error(result.message);
        }
    })
    .catch(error => {
        console.error('Error saving settings:', error);
        alert('Error saving settings. Please try again.');
    });
}

// Tax Rates
function openTaxModal(tax = null) {
    document.getElementById('taxModalTitle').textContent = tax ? 'Edit Tax Rate' : 'Add Tax Rate';
    document.getElementById('taxForm').reset();
    document.getElementById('taxId').value = tax ? tax.id : '';
    if (tax) {
        document.getElementById('taxName').value = tax.name;
        document.getElementById('taxRate').value = tax.rate;
    }
    document.getElementById('taxModal').classList.remove('hidden');
}

function closeTaxModal() {
    document.getElementById('taxModal').classList.add('hidden');
}

function editTax(tax) {
    openTaxModal(tax);
}

function saveTax(event) {
    event.preventDefault();

    const taxId = document.getElementById('taxId').value;
    const data = {
        name: document.getElementById('taxName').value,
        rate: parseFloat(document.getElementById('taxRate').value)
    };

    if (taxId) {
        data.id = taxId;
    }

    fetch('../api/tax_rates.php', {
        method: taxId ? 'PUT' : 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            throw new Error(result.message);
        }
    })
    .catch(error => {
        console.error('Error saving tax rate:', error);
        alert('Error saving tax rate. Please try again.');
    });
}

function deleteTax(id) {
    if (!confirm('Are you sure you want to delete this tax rate?')) {
        return;
    }

    fetch('../api/tax_rates.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            throw new Error(result.message);
        }
    })
    .catch(error => {
        console.error('Error deleting tax rate:', error);
        alert('Error deleting tax rate. Please try again.');
    });
}

// Payment Methods
function openPaymentMethodModal(method = null) {
    document.getElementById('paymentMethodModalTitle').textContent = method ? 'Edit Payment Method' : 'Add Payment Method';
    document.getElementById('paymentMethodForm').reset();
    document.getElementById('paymentMethodId').value = method ? method.id : '';
    if (method) {
        document.getElementById('paymentMethodName').value = method.name;
    }
    document.getElementById('paymentMethodModal').classList.remove('hidden');
}

function closePaymentMethodModal() {
    document.getElementById('paymentMethodModal').classList.add('hidden');
}

function editPaymentMethod(method) {
    openPaymentMethodModal(method);
}

function savePaymentMethod(event) {
    event.preventDefault();

    const methodId = document.getElementById('paymentMethodId').value;
    const data = {
        name: document.getElementById('paymentMethodName').value
    };

    if (methodId) {
        data.id = methodId;
    }

    fetch('../api/payment_methods.php', {
        method: methodId ? 'PUT' : 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            throw new Error(result.message);
        }
    })
    .catch(error => {
        console.error('Error saving payment method:', error);
        alert('Error saving payment method. Please try again.');
    });
}

function deletePaymentMethod(id) {
    if (!confirm('Are you sure you want to delete this payment method?')) {
        return;
    }

    fetch('../api/payment_methods.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            throw new Error(result.message);
        }
    })
    .catch(error => {
        console.error('Error deleting payment method:', error);
        alert('Error deleting payment method. Please try again.');
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
