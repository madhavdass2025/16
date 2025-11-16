<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager', 'Cashier']);
include 'includes/header.php';
include 'includes/sidebar.php';

$customers_result = $mysqli->query("SELECT customer_id, name FROM customers ORDER BY name ASC");
$customers = $customers_result->fetch_all(MYSQLI_ASSOC);

$products_result = $mysqli->query("SELECT product_id, product_name FROM products ORDER BY product_name ASC");
$products = $products_result->fetch_all(MYSQLI_ASSOC);
?>

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
                    <th>MRP</th>
                    <th>GST Rate (%)</th>
                    <th>Price (excl. Tax)</th>
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
                        <th>Total CGST</th>
                        <td id="total-cgst">0.00</td>
                    </tr>
                    <tr>
                        <th>Total SGST</th>
                        <td id="total-sgst">0.00</td>
                    </tr>
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

<?php
$product_options_html = '';
foreach ($products as $product) {
    $product_options_html .= "<option value='{$product['product_id']}'>" . htmlspecialchars($product['product_name']) . "</option>";
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
        <td><input type="text" class="form-control mrp" readonly></td>
        <td><input type="text" class="form-control gst-rate" readonly></td>
        <td><input type="text" class="form-control price-excl-tax" readonly></td>
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
    const responseMessage = document.getElementById('response-message');
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
        const mrp = parseFloat(row.querySelector('.mrp').value) || 0;
        const taxRate = (parseFloat(row.querySelector('.gst-rate').value) || 0) / 100;

        if (quantity > 0 && mrp > 0) {
            const singleItemTaxAmount = mrp - (mrp / (1 + taxRate));
            const singleItemPriceExclTax = mrp - singleItemTaxAmount;

            const totalAmount = mrp * quantity;
            const totalTaxAmount = singleItemTaxAmount * quantity;
            const totalPriceExclTax = singleItemPriceExclTax * quantity;

            row.querySelector('.price-excl-tax').value = totalPriceExclTax.toFixed(2);
            row.querySelector('.tax-amount').value = totalTaxAmount.toFixed(2);
            row.querySelector('.total-amount').value = totalAmount.toFixed(2);
        } else {
            row.querySelector('.price-excl-tax').value = '0.00';
            row.querySelector('.tax-amount').value = '0.00';
            row.querySelector('.total-amount').value = '0.00';
        }
        updateTotals();
    }

    billingItemsTable.addEventListener('change', function(e) {
        if (e.target.classList.contains('product-select')) {
            const row = e.target.closest('tr');
            const productId = e.target.value;
            if (!productId) {
                row.querySelector('.mrp').value = '';
                row.querySelector('.gst-rate').value = '';
                calculateRow(row);
                return;
            };

            fetch(`ajax_sales_entry.php?action=get_product_details&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.mrp) {
                        row.querySelector('.mrp').value = parseFloat(data.mrp).toFixed(2);
                        row.querySelector('.gst-rate').value = (parseFloat(data.tax_rate) * 100).toFixed(2);
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

    billingItemsTable.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item-btn')) {
            e.target.closest('tr').remove();
            updateTotals();
        }
    });

    function updateTotals() {
        let totalCgst = 0;
        let totalSgst = 0;
        let totalPayable = 0;

        billingItemsTable.querySelectorAll('tr').forEach(row => {
            const taxAmount = parseFloat(row.querySelector('.tax-amount').value) || 0;
            const totalAmount = parseFloat(row.querySelector('.total-amount').value) || 0;

            totalCgst += taxAmount / 2;
            totalSgst += taxAmount / 2;
            totalPayable += totalAmount;
        });

        document.getElementById('total-cgst').textContent = totalCgst.toFixed(2);
        document.getElementById('total-sgst').textContent = totalSgst.toFixed(2);
        document.getElementById('total-payable').textContent = totalPayable.toFixed(2);
    }

    salesForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(salesForm);

        fetch('ajax_sales_entry.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            responseMessage.style.display = 'block';
            if (data.status === 'success') {
                responseMessage.className = 'alert alert-success';
                responseMessage.textContent = data.message;
                salesForm.reset();
                billingItemsTable.innerHTML = '';
                addBillingRow();
                updateTotals();
            } else {
                responseMessage.className = 'alert alert-danger';
                responseMessage.textContent = data.message;
            }
        });
    });

    addBillingRow();
});
</script>

<?php
include 'includes/footer.php';
?>