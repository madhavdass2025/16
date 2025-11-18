<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager']);
include 'includes/header.php';
include 'includes/sidebar.php';

$suppliers_result = $mysqli->query("SELECT supplier_id, name FROM suppliers ORDER BY name ASC");
$suppliers = $suppliers_result->fetch_all(MYSQLI_ASSOC);
$products_result = $mysqli->query("SELECT Mid, name FROM products ORDER BY name ASC");
$products = $products_result->fetch_all(MYSQLI_ASSOC);
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
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
                        <th>Total</th>
                        <th><button type="button" class="btn btn-sm btn-success" id="add-row">Add</button></th>
                    </tr>
                </thead>
                <tbody id="purchase-items">
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end">
            <h4>Grand Total: <span id="grand-total">0.00</span></h4>
        </div>

        <button type="submit" class="btn btn-primary">Save Purchase</button>
    </form>
</main>

<?php
$product_options_html = '';
foreach ($products as $product) {
    $product_options_html .= "<option value='{$product['Mid']}'>" . htmlspecialchars($product['name']) . "</option>";
}
?>

<template id="purchase-item-template">
    <tr>
        <td>
            <select class="form-select product-select" name="items[__INDEX__][product_id]" required>
                <option value="">Select a Product</option>
                <?php echo $product_options_html; ?>
            </select>
        </td>
        <td><input type="text" class="form-control" name="items[__INDEX__][batch_number]" required></td>
        <td><input type="date" class="form-control" name="items[__INDEX__][expiry_date]" required></td>
        <td><input type="number" step="1" min="1" class="form-control quantity" name="items[__INDEX__][quantity]" required></td>
        <td><input type="number" step="0.01" min="0" class="form-control price" name="items[__INDEX__][purchase_price]" required></td>
        <td><input type="text" class="form-control total" readonly></td>
        <td><button type="button" class="btn btn-sm btn-danger remove-row" data-row-id="__INDEX__">Remove</button></td>
    </tr>
</template>

<script>
// The JavaScript for this page does not need to change as it already uses product_id
</script>

<?php
include 'includes/footer.php';
?>