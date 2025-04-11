<?php 
require_once '../includes/header.php';
?>

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Table Management</h1>
        <button 
            onclick="openTableModal()"
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Add New Table
        </button>
    </div>

    <!-- Tables Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4" id="tablesGrid">
        <!-- Tables will be loaded here -->
    </div>
</div>

<!-- Table Modal -->
<div id="tableModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 id="modalTitle" class="text-xl font-bold mb-4">Add New Table</h2>
        
        <form id="tableForm" onsubmit="saveTable(event)">
            <input type="hidden" id="tableId">
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="tableName">
                    Table Name/Number
                </label>
                <input 
                    type="text" 
                    id="tableName" 
                    class="w-full p-2 border rounded" 
                    required>
            </div>

            <div class="flex justify-end space-x-2">
                <button 
                    type="button"
                    onclick="closeTableModal()"
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
// Load tables
function loadTables() {
    fetch('../api/tables.php')
        .then(response => response.json())
        .then(tables => {
            const grid = document.getElementById('tablesGrid');
            grid.innerHTML = tables.map(table => `
                <div class="relative bg-white border rounded-lg p-4 flex flex-col items-center">
                    <div class="absolute top-2 right-2 space-x-1">
                        <button 
                            onclick="editTable(${JSON.stringify(table).replace(/"/g, '"')})"
                            class="text-blue-500 hover:text-blue-700">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button 
                            onclick="deleteTable(${table.id})"
                            class="text-red-500 hover:text-red-700">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="text-6xl mb-2">
                        <i class="fas fa-utensils ${table.status === 'open' ? 'text-yellow-500' : 'text-gray-400'}"></i>
                    </div>
                    <div class="text-lg font-semibold">Table ${table.name}</div>
                    <div class="text-sm text-gray-500">${table.status === 'open' ? 'Occupied' : 'Available'}</div>
                </div>
            `).join('');
        })
        .catch(error => {
            console.error('Error loading tables:', error);
            alert('Error loading tables. Please try again.');
        });
}

// Open modal for new table
function openTableModal() {
    document.getElementById('modalTitle').textContent = 'Add New Table';
    document.getElementById('tableForm').reset();
    document.getElementById('tableId').value = '';
    document.getElementById('tableModal').classList.remove('hidden');
}

// Close modal
function closeTableModal() {
    document.getElementById('tableModal').classList.add('hidden');
}

// Edit table
function editTable(table) {
    document.getElementById('modalTitle').textContent = 'Edit Table';
    document.getElementById('tableId').value = table.id;
    document.getElementById('tableName').value = table.name;
    document.getElementById('tableModal').classList.remove('hidden');
}

// Save table
function saveTable(event) {
    event.preventDefault();

    const tableId = document.getElementById('tableId').value;
    const data = {
        name: document.getElementById('tableName').value
    };

    if (tableId) {
        data.id = tableId;
    }

    fetch('../api/tables.php', {
        method: tableId ? 'PUT' : 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            closeTableModal();
            loadTables();
        } else {
            throw new Error(result.message);
        }
    })
    .catch(error => {
        console.error('Error saving table:', error);
        alert('Error saving table. Please try again.');
    });
}

// Delete table
function deleteTable(id) {
    if (!confirm('Are you sure you want to delete this table?')) {
        return;
    }

    fetch('../api/tables.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            loadTables();
        } else {
            throw new Error(result.message);
        }
    })
    .catch(error => {
        console.error('Error deleting table:', error);
        alert('Error deleting table. Please try again.');
    });
}

// Initial load
loadTables();

// Refresh tables every 30 seconds to update status
setInterval(loadTables, 30000);
</script>

<?php require_once '../includes/footer.php'; ?>
