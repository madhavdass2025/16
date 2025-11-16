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

<form id="sales-form">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="customer_id" class="form-label">Customer</label>
                <select class="form-select" id="customer_id">
                    <option value="">Select Customer</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['customer_id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="product_search" class="form-label">Search Product</label>
                <input type="text" class="form-control" id="product_search">
                <div id="search-results" class="list-group position-absolute" style="z-index: 1000;"></div>
            </div>
        </div>
    </div>

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
            <tbody id="cart-items-table">
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-end mt-3">
        <h4>Total: <span id="cart-total">0.00</span></h4>
    </div>

    <button type="button" class="btn btn-primary mt-3" id="process-sale-btn" data-bs-toggle="modal" data-bs-target="#payment-modal">Process Sale</button>
</form>

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

    productSearch.addEventListener('keyup', function() {
        const term = productSearch.value;
        if (term.length < 2) {
            searchResults.innerHTML = '';
            return;
        }

        fetch(`ajax_sales_entry.php?action=search_products&term=${term}`)
            .then(response => response.json())
            .then(data => {
                let html = '';
                data.forEach(product => {
                    html += `<a href="#" class="list-group-item list-group-item-action add-to-cart" data-product-id="${product.product_id}" data-product-name="${product.product_name}" data-price="${product.mrp}">${product.product_name} - $${product.mrp}</a>`;
                });
                searchResults.innerHTML = html;
            });
    });

    searchResults.addEventListener('click', function(e) {
        e.preventDefault();
        if (e.target.classList.contains('add-to-cart')) {
            const productId = e.target.dataset.productId;
            const productName = e.target.dataset.productName;
            const price = e.target.dataset.price;

            const existingItem = cart.find(item => item.id === productId);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({ id: productId, name: productName, price: parseFloat(price), quantity: 1 });
            }
            renderCart();
            productSearch.value = '';
            searchResults.innerHTML = '';
        }
    });

    function renderCart() {
        let tableHtml = '';
        totalAmount = 0;

        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            totalAmount += itemTotal;
            tableHtml += `
                <tr>
                    <td>${item.name}</td>
                    <td>${item.price.toFixed(2)}</td>
                    <td><input type="number" class="form-control cart-quantity" data-index="${index}" value="${item.quantity}" min="1"></td>
                    <td>${itemTotal.toFixed(2)}</td>
                    <td><button type="button" class="btn btn-sm btn-danger remove-from-cart" data-index="${index}">Remove</button></td>
                </tr>
            `;
        });

        cartItemsTable.innerHTML = tableHtml;
        cartTotalSpan.textContent = totalAmount.toFixed(2);
        paymentTotalSpan.textContent = totalAmount.toFixed(2);
        updatePaymentBalance();
    }

    cartItemsTable.addEventListener('change', function(e) {
        if (e.target.classList.contains('cart-quantity')) {
            const index = e.target.dataset.index;
            const newQuantity = parseInt(e.target.value);
            if (newQuantity > 0) {
                cart[index].quantity = newQuantity;
                renderCart();
            }
        }
    });

    cartItemsTable.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-from-cart')) {
            const index = e.target.dataset.index;
            cart.splice(index, 1);
            renderCart();
        }
    });

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
        if (cart.length === 0) {
            alert('The cart is empty.');
            return;
        }

        const customerId = customerIdSelect.value;
        if (!customerId) {
            alert('Please select a customer.');
            return;
        }

        const saleData = new FormData();
        saleData.append('customer_id', customerId);
        cart.forEach((item, index) => {
            saleData.append(`items[${index}][product_id]`, item.id);
            saleData.append(`items[${index}][quantity]`, item.quantity);
            saleData.append(`items[${index}][price]`, item.price);
        });

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
            responseMessage.style.display = 'block';
            if (data.status === 'success') {
                responseMessage.className = 'alert alert-success';
                responseMessage.textContent = data.message;
                cart = [];
                renderCart();
                customerIdSelect.value = '';
            } else {
                responseMessage.className = 'alert alert-danger';
                responseMessage.textContent = data.message;
            }
        });
    });
});
</script>

<?php
include 'includes/footer.php';
?>