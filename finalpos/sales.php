<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager', 'Cashier']);
include 'includes/header.php';
include 'includes/sidebar.php';

$customers_result = $mysqli->query("SELECT customer_id, name FROM customers ORDER BY name ASC");
$customers = $customers_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Point of Sale</h1>
</div>

<div id="response-message" class="alert" style="display: none;"></div>

<div class="row">
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-6">
                <input type="text" class="form-control" placeholder="Search for products..." id="product-search">
                <div id="search-results" class="list-group"></div>
            </div>
            <div class="col-md-6">
                <select class="form-select" id="customer_id">
                    <option value="">Select Customer</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['customer_id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <form id="sales-form">
            <div class="table-responsive mt-3">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="cart-items-table"></tbody>
                </table>
            </div>
        </form>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Sale Summary</h5>
                <hr>
                <h4>Total: <span id="cart-total">0.00</span></h4>
                <button type="button" class="btn btn-primary w-100" id="process-sale-btn" data-bs-toggle="modal" data-bs-target="#payment-modal">Process Sale</button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="payment-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h4 class="mb-3">Total Due: <span id="payment-total">0.00</span></h4>
                <div class="mb-3">
                    <label for="cash-payment" class="form-label">Cash</label>
                    <input type="number" step="0.01" class="form-control payment-input" id="cash-payment" data-method="Cash" value="0">
                </div>
                <div class="mb-3">
                    <label for="card-payment" class="form-label">Card</label>
                    <input type="number" step="0.01" class="form-control payment-input" id="card-payment" data-method="Card" value="0">
                </div>
                <div class="mb-3">
                    <label for="upi-payment" class="form-label">UPI</label>
                    <input type="number" step="0.01" class="form-control payment-input" id="upi-payment" data-method="UPI" value="0">
                </div>
                <hr>
                <h5>Balance: <span id="payment-balance">0.00</span></h5>
                <button type="button" class="btn btn-primary w-100" id="confirm-payment-btn">Confirm Payment</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSearch = document.getElementById('product-search');
    const searchResults = document.getElementById('search-results');
    const cartItemsTable = document.getElementById('cart-items-table');
    const cartTotalSpan = document.getElementById('cart-total');
    const responseMessage = document.getElementById('response-message');
    const customerIdSelect = document.getElementById('customer_id');
    const paymentModal = new bootstrap.Modal(document.getElementById('payment-modal'));
    const paymentTotalSpan = document.getElementById('payment-total');
    const paymentBalanceSpan = document.getElementById('payment-balance');
    const paymentInputs = document.querySelectorAll('.payment-input');
    const confirmPaymentBtn = document.getElementById('confirm-payment-btn');
    let cart = [];
    let totalAmount = 0;

    // ... (product search and cart rendering logic remains the same)

    function renderCart() {
        let tableHtml = '';
        totalAmount = 0;
        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            totalAmount += itemTotal;
            // ... (rest of renderCart)
        });
        cartTotalSpan.textContent = totalAmount.toFixed(2);
        paymentTotalSpan.textContent = totalAmount.toFixed(2);
        updatePaymentBalance();
    }

    paymentInputs.forEach(input => {
        input.addEventListener('input', updatePaymentBalance);
    });

    function updatePaymentBalance() {
        let paidAmount = 0;
        paymentInputs.forEach(input => {
            paidAmount += parseFloat(input.value) || 0;
        });
        const balance = totalAmount - paidAmount;
        paymentBalanceSpan.textContent = balance.toFixed(2);
    }

    confirmPaymentBtn.addEventListener('click', function() {
        // ... (validation checks for cart and customer)

        const saleData = new FormData();
        saleData.append('customer_id', customerIdSelect.value);
        cart.forEach((item, index) => { /* ... */ });

        let paymentIndex = 0;
        paymentInputs.forEach(input => {
            const amount = parseFloat(input.value) || 0;
            if (amount > 0) {
                saleData.append(`payments[${paymentIndex}][method]`, input.dataset.method);
                saleData.append(`payments[${paymentIndex}][amount]`, amount);
                paymentIndex++;
            }
        });

        fetch('ajax_sales_entry.php', {
            method: 'POST',
            body: saleData
        })
        .then(response => response.json())
        .then(data => {
            paymentModal.hide();
            // ... (rest of the response handling)
        });
    });
});
</script>

<?php
include 'includes/footer.php';
?>