<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager', 'Cashier']);
include 'includes/header.php';
// The sidebar is intentionally removed for a full-screen POS view

$customers_result = $mysqli->query("SELECT customer_id, name FROM customers ORDER BY name ASC");
$customers = $customers_result->fetch_all(MYSQLI_ASSOC);
?>

<main class="col-12">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Point of Sale</h1>
    </div>

    <div id="response-message" class="alert" style="display: none;"></div>

    <div id="pos-container">
        <div id="product-selection">
            <h5>Categories</h5>
            <div id="category-grid" class="mb-4"></div>
            <hr>
            <h5>Products</h5>
            <div id="product-grid"></div>
        </div>

        <div id="cart-panel" class="card">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">Bill Details</h5>
                <div class="mb-3">
                    <select class="form-select" id="customer_id">
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo $customer['customer_id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-grow-1" style="overflow-y: auto;">
                    <table class="table table-sm">
                        <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                        <tbody id="cart-items-table"></tbody>
                    </table>
                </div>
                <div>
                    <h4>Total: <span id="cart-total">0.00</span></h4>
                    <hr>
                    <div class="payment-methods">
                        <div class="input-group mb-2">
                            <span class="input-group-text">Cash</span>
                            <input type="number" class="form-control payment-input" data-method="Cash" placeholder="0.00">
                        </div>
                        <div class="input-group mb-2">
                            <span class="input-group-text">Card</span>
                            <input type="number" class="form-control payment-input" data-method="Card" placeholder="0.00">
                        </div>
                        <div class="input-group mb-2">
                            <span class="input-group-text">UPI</span>
                            <input type="number" class="form-control payment-input" data-method="UPI" placeholder="0.00">
                        </div>
                    </div>
                    <h5>Balance: <span id="payment-balance">0.00</span></h5>
                    <button class="btn btn-primary w-100 mt-3" id="confirm-payment-btn">Finalize Sale</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Advanced POS JavaScript is already implemented.
</script>

<?php
include 'includes/footer.php';
?>