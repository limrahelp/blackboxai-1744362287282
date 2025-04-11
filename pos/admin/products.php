<?php 
require_once '../includes/header.php';
?>

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Product Management</h1>
        <button 
            onclick="openProductModal()"
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Add New Product
        </button>
    </div>

    <!-- Search and Filter -->
    <div class="mb-6">
        <input 
            type="text" 
            id="searchProduct" 
            class="w-full p-2 border rounded" 
            placeholder="Search products...">
    </div>

    <!-- Products Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Short Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody id="productsTableBody">
                <!-- Products will be loaded here -->
            </tbody>
        </table>
    </div>
</div>

<!-- Product Modal -->
<div id="productModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 id="modalTitle" class="text-xl font-bold mb-4">Add New Product</h2>
        
        <form id="productForm" onsubmit="saveProduct(event)">
            <input type="hidden" id="productId">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="productName">
                    Product Name
                </label>
                <input 
                    type="text" 
                    id="productName" 
                    class="w-full p-2 border rounded" 
                    required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="shortCode">
                    Short Code
                </label>
                <input 
                    type="text" 
                    id="shortCode" 
                    class="w-full p-2 border rounded" 
                    required>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="price">
                    Price
                </label>
                <input 
                    type="number" 
                    id="price" 
                    class="w-full p-2 border rounded" 
                    step="0.01" 
                    min="0" 
                    required>
            </div>

            <div class="flex justify-end space-x-2">
                <button 
                    type="button"
                    onclick="closeProductModal()"
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
// Load products
function loadProducts(search = '') {
    fetch(`../api/products.php${search ? '?search=' + encodeURIComponent(search) : ''}`)
        .then(response => response.json())
        .then(products => {
            const tbody = document.getElementById('productsTableBody');
            tbody.innerHTML = products.map(product => `
                <tr class="border-b">
                    <td class="px-6 py-4">${product.name}</td>
                    <td class="px-6 py-4">${product.short_code}</td>
                    <td class="px-6 py-4">₹${parseFloat(product.price).toFixed(2)}</td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <button 
                            onclick="editProduct(${JSON.stringify(product).replace(/"/g, '"')})"
                            class="text-blue-500 hover:text-blue-700">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button 
                            onclick="deleteProduct(${product.id})"
                            class="text-red-500 hover:text-red-700">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        })
        .catch(error => {
            console.error('Error loading products:', error);
            alert('Error loading products. Please try again.');
        });
}

// Open modal for new product
function openProductModal() {
    document.getElementById('modalTitle').textContent = 'Add New Product';
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('productModal').classList.remove('hidden');
}

// Close modal
function closeProductModal() {
    document.getElementById('productModal').classList.add('hidden');
}

// Edit product
function editProduct(product) {
    document.getElementById('modalTitle').textContent = 'Edit Product';
    document.getElementById('productId').value = product.id;
    document.getElementById('productName').value = product.name;
    document.getElementById('shortCode').value = product.short_code;
    document.getElementById('price').value = product.price;
    document.getElementById('productModal').classList.remove('hidden');
}

// Save product
function saveProduct(event) {
    event.preventDefault();

    const productId = document.getElementById('productId').value;
    const data = {
        name: document.getElementById('productName').value,
        short_code: document.getElementById('shortCode').value,
        price: parseFloat(document.getElementById('price').value)
    };

    if (productId) {
        data.id = productId;
    }

    fetch('../api/products.php', {
        method: productId ? 'PUT' : 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            closeProductModal();
            loadProducts();
        } else {
            throw new Error(result.message);
        }
    })
    .catch(error => {
        console.error('Error saving product:', error);
        alert('Error saving product. Please try again.');
    });
}

// Delete product
function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) {
        return;
    }

    fetch('../api/products.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            loadProducts();
        } else {
            throw new Error(result.message);
        }
    })
    .catch(error => {
        console.error('Error deleting product:', error);
        alert('Error deleting product. Please try again.');
    });
}

// Search products
document.getElementById('searchProduct').addEventListener('input', function(e) {
    loadProducts(e.target.value);
});

// Initial load
loadProducts();
</script>

<?php require_once '../includes/footer.php'; ?>
