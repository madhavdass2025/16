<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager', 'Cashier']);
include 'includes/header.php'; // This opens `<div class="container-fluid"><div class="row">`

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
</div> <!-- This closes the `<div class="row">` from header.php -->
</div> <!-- This closes the `<div class="container-fluid">` from header.php -->


<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryGrid = document.getElementById('category-grid');
    const productGrid = document.getElementById('product-grid');
    const cartItemsTable = document.getElementById('cart-items-table');
    const cartTotalSpan = document.getElementById('cart-total');
    const customerIdSelect = document.getElementById('customer_id');
    const paymentBalanceSpan = document.getElementById('payment-balance');
    const paymentInputs = document.querySelectorAll('.payment-input');
    const confirmPaymentBtn = document.getElementById('confirm-payment-btn');
    const responseMessage = document.getElementById('response-message');
    let cart = [];
    let totalAmount = 0;

    function loadCategories() {
        fetch('ajax_sales_entry.php?action=get_categories')
            .then(response => response.json())
            .then(data => {
                let html = '';
                data.forEach(category => {
                    html += `<button type="button" class="btn btn-outline-primary category-btn" data-category-id="${category.category_id}">${category.category_name}</button>`;
                });
                categoryGrid.innerHTML = html;
            });
    }

    function loadProducts(categoryId) {
        fetch(`ajax_sales_entry.php?action=get_products&category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                let html = '';
                data.forEach(product => {
                    html += `<button type="button" class="btn btn-outline-secondary product-btn" data-product-id="${product.product_id}" data-product-name="${product.product_name}" data-price="${product.mrp}">${product.product_name}<br>$${product.mrp}</button>`;
                });
                productGrid.innerHTML = html;
            });
    }

    categoryGrid.addEventListener('click', function(e) {
        if (e.target.classList.contains('category-btn')) {
            loadProducts(e.target.dataset.categoryId);
        }
    });

    productGrid.addEventListener('click', function(e) {
        if (e.target.classList.contains('product-btn')) {
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
        }
    });

    function renderCart() {
        let tableHtml = '';
        totalAmount = 0;
        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            totalAmount += itemTotal;
            tableHtml += `<tr>
                <td>${item.name}</td>
                <td><input type="number" class="form-control form-control-sm cart-quantity" value="${item.quantity}" data-product-id="${item.id}"></td>
                <td>${item.price.toFixed(2)}</td>
                <td>${itemTotal.toFixed(2)}</td>
            </tr>`;
        });
        cartItemsTable.innerHTML = tableHtml;
        cartTotalSpan.textContent = totalAmount.toFixed(2);
        updatePaymentBalance();
    }

    cartItemsTable.addEventListener('change', function(e) {
        if (e.target.classList.contains('cart-quantity')) {
            const productId = e.target.dataset.productId;
            const newQuantity = parseInt(e.target.value);
            const item = cart.find(i => i.id === productId);
            if (item) {
                if (newQuantity > 0) {
                    item.quantity = newQuantity;
                } else {
                    cart = cart.filter(i => i.id !== productId);
                }
                renderCart();
            }
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
        if (cart.length === 0 || !customerIdSelect.value) {
            alert('Please select a customer and add items to the cart.');
            return;
        }

        const saleData = new FormData();
        saleData.append('customer_id', customerIdSelect.value);
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
            responseMessage.style.display = 'block';
            if (data.status === 'success') {
                responseMessage.className = 'alert alert-success';
                responseMessage.textContent = data.message;
                cart = [];
                renderCart();
                customerIdSelect.value = '';
                paymentInputs.forEach(input => input.value = '0');
            } else {
                responseMessage.className = 'alert alert-danger';
                responseMessage.textContent = data.message;
            }
        });
    });

    loadCategories();
});
</script>

<?php
include 'includes/footer.php';
?>