<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager']);
include 'includes/header.php';
include 'includes/sidebar.php';

// Fetch suppliers and products from the database
$suppliers_result = $mysqli->query("SELECT supplier_id, name FROM suppliers ORDER BY name ASC");
$suppliers = $suppliers_result->fetch_all(MYSQLI_ASSOC);
$products_result = $mysqli->query("SELECT product_id, product_name FROM products ORDER BY product_name ASC");
$products = $products_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Purchase Entry</h1>
</div>

<div id="response-message" class="alert" style="display: none;"></div>

<form id="purchase-form">
    <div class="row">
        <div class="col-md-4">
            <div class="mb-3">
                <label for="supplier_id" class="form-label">Supplier</label>
                <select class="form-select" id="supplier_id" name="supplier_id" required>
                    <option value="">Select a Supplier</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo $supplier['supplier_id']; ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="invoice_number" class="form-label">Invoice Number</label>
                <input type="text" class="form-control" id="invoice_number" name="invoice_number" required>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="purchase_date" class="form-label">Purchase Date</label>
                <input type="date" class="form-control" id="purchase_date" name="invoice_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Batch Number</th>
                    <th>Expiry Date</th>
                    <th>Quantity</th>
                    <th>Purchase Price</th>
                    <th>Selling Price</th>
                    <th>Total</th>
                    <th><button type="button" class="btn btn-sm btn-success" id="add-row">Add</button></th>
                </tr>
            </thead>
            <tbody id="purchase-items">
                <!-- Rows will be added dynamically here -->
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-end">
        <h4>Grand Total: <span id="grand-total">0.00</span></h4>
    </div>

    <button type="submit" class="btn btn-primary">Save Purchase</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const purchaseForm = document.getElementById('purchase-form');
    const addRowBtn = document.getElementById('add-row');
    const purchaseItems = document.getElementById('purchase-items');
    const grandTotalSpan = document.getElementById('grand-total');
    const responseMessage = document.getElementById('response-message');
    let rowCount = 0;

    addRowBtn.addEventListener('click', function() {
        rowCount++;
        const row = `
            <tr id="row-${rowCount}">
                <td>
                    <select class="form-select product-select" name="items[${rowCount}][product_id]" required>
                        <option value="">Select a Product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['product_id']; ?>"><?php echo htmlspecialchars($product['product_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="text" class="form-control" name="items[${rowCount}][batch_number]" required></td>
                <td><input type="date" class="form-control" name="items[${rowCount}][expiry_date]" required></td>
                <td><input type="number" step="1" min="1" class="form-control quantity" name="items[${rowCount}][quantity]" required></td>
                <td><input type="number" step="0.01" min="0" class="form-control price" name="items[${rowCount}][purchase_price]" required></td>
                <td><input type="number" step="0.01" min="0" class="form-control" name="items[${rowCount}][selling_price]" required></td>
                <td><input type="text" class="form-control total" readonly></td>
                <td><button type="button" class="btn btn-sm btn-danger remove-row" data-row-id="${rowCount}">Remove</button></td>
            </tr>
        `;
        purchaseItems.insertAdjacentHTML('beforeend', row);
    });

    purchaseItems.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            const rowId = e.target.dataset.rowId;
            document.getElementById(`row-${rowId}`).remove();
            updateGrandTotal();
        }
    });

    purchaseItems.addEventListener('input', function(e) {
        if (e.target.classList.contains('quantity') || e.target.classList.contains('price')) {
            const row = e.target.closest('tr');
            const quantity = row.querySelector('.quantity').value;
            const price = row.querySelector('.price').value;
            const total = quantity * price;
            row.querySelector('.total').value = total.toFixed(2);
            updateGrandTotal();
        }
    });

    function updateGrandTotal() {
        let grandTotal = 0;
        const totals = purchaseItems.querySelectorAll('.total');
        totals.forEach(function(total) {
            grandTotal += parseFloat(total.value) || 0;
        });
        grandTotalSpan.textContent = grandTotal.toFixed(2);
    }

    purchaseForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(purchaseForm);

        fetch('ajax_purchase_entry.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            responseMessage.style.display = 'block';
            if (data.status === 'success') {
                responseMessage.className = 'alert alert-success';
                responseMessage.textContent = data.message;
                purchaseForm.reset();
                purchaseItems.innerHTML = '';
                grandTotalSpan.textContent = '0.00';
            } else {
                responseMessage.className = 'alert alert-danger';
                responseMessage.textContent = data.message;
            }
        })
        .catch(error => {
            responseMessage.style.display = 'block';
            responseMessage.className = 'alert alert-danger';
            responseMessage.textContent = 'An error occurred while submitting the form.';
        });
    });
});
</script>

<?php
include 'includes/footer.php';
?>