<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager', 'Cashier']);
include 'includes/header.php';
include 'includes/sidebar.php';

$customers_result = $mysqli->query("SELECT customer_id, name FROM customers ORDER BY name ASC");
$customers = $customers_result->fetch_all(MYSQLI_ASSOC);

$products_result = $mysqli->query("SELECT Mid, name FROM products ORDER BY name ASC");
$products = $products_result->fetch_all(MYSQLI_ASSOC);
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">New Sale / Billing</h1>
    </div>

    <div id="response-message" class="alert" style="display: none;"></div>

    <form id="sales-form">
        <div class="row">
            <div class="col-md-6">
                <label for="customer_id" class="form-label">Customer</label>
                <select class="form-select" id="customer_id" name="customer_id" required>
                    <option value="">Select Customer</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['customer_id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="invoice_date" class="form-label">Date</label>
                <input type="date" class="form-control" id="invoice_date" name="invoice_date" value="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>

        <div class="table-responsive mt-4">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 25%;">Product</th>
                        <th style="width: 10%;">Quantity</th>
                        <th>Unit Price</th>
                        <th>Tax Amount</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="billing-items-table">
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-primary" id="add-item-btn">Add Item</button>

        <div class="row justify-content-end mt-4">
            <div class="col-md-4">
                <table class="table">
                    <tbody>
                        <tr>
                            <th>Total Payable</th>
                            <td id="total-payable">0.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <button type="submit" class="btn btn-success">Save Invoice</button>
    </form>
</main>

<?php
$product_options_html = '';
foreach ($products as $product) {
    $product_options_html .= "<option value='{$product['Mid']}'>" . htmlspecialchars($product['name']) . "</option>";
}
?>

<template id="billing-item-template">
    <tr>
        <td>
            <select class="form-select product-select" name="items[__INDEX__][product_id]">
                <option value="">Select Product</option>
                <?php echo $product_options_html; ?>
            </select>
        </td>
        <td><input type="number" class="form-control quantity" name="items[__INDEX__][quantity]" value="1" min="1"></td>
        <td><input type="text" class="form-control unit-price" readonly></td>
        <td><input type="text" class="form-control tax-amount" readonly></td>
        <td><input type="text" class="form-control total-amount" readonly></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-item-btn">Remove</button></td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const salesForm = document.getElementById('sales-form');
    const addItemBtn = document.getElementById('add-item-btn');
    const billingItemsTable = document.getElementById('billing-items-table');
    const itemTemplate = document.getElementById('billing-item-template');
    let itemIndex = 0;

    function addBillingRow() {
        const newRow = itemTemplate.content.cloneNode(true);
        const newRowTr = newRow.querySelector('tr');
        newRowTr.innerHTML = newRowTr.innerHTML.replace(/__INDEX__/g, itemIndex);
        billingItemsTable.appendChild(newRow);
        itemIndex++;
    }

    addItemBtn.addEventListener('click', addBillingRow);

    function calculateRow(row) {
        const quantity = parseInt(row.querySelector('.quantity').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
        const taxAmount = parseFloat(row.querySelector('.tax-amount').value) || 0;

        if (quantity > 0 && unitPrice > 0) {
            const total = (unitPrice + taxAmount) * quantity;
            row.querySelector('.total-amount').value = total.toFixed(2);
        } else {
            row.querySelector('.total-amount').value = '0.00';
        }
        updateTotals();
    }

    billingItemsTable.addEventListener('change', function(e) {
        if (e.target.classList.contains('product-select')) {
            const row = e.target.closest('tr');
            const productId = e.target.value;
            if (!productId) {
                row.querySelector('.unit-price').value = '';
                row.querySelector('.tax-amount').value = '';
                calculateRow(row);
                return;
            };

            fetch(`ajax_sales_entry.php?action=get_product_details&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        row.querySelector('.unit-price').value = parseFloat(data.UnitPrice).toFixed(2);
                        row.querySelector('.tax-amount').value = parseFloat(data.taxAmount).toFixed(2);
                        calculateRow(row);
                    }
                });
        }
    });

    billingItemsTable.addEventListener('input', function(e) {
        if (e.target.classList.contains('quantity')) {
            const row = e.target.closest('tr');
            calculateRow(row);
        }
    });

    // ... (rest of the JavaScript)
});
</script>

<?php
include 'includes/footer.php';
?>