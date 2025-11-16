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
                    <tbody id="cart-items-table">
                        <!-- Cart items will be added here -->
                    </tbody>
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
                <button type="button" class="btn btn-primary w-100" id="process-sale">Process Sale</button>
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
    const processSaleBtn = document.getElementById('process-sale');
    const responseMessage = document.getElementById('response-message');
    const customerIdSelect = document.getElementById('customer_id');
    let cart = [];

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
        let total = 0;

        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
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
        cartTotalSpan.textContent = total.toFixed(2);
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

    processSaleBtn.addEventListener('click', function() {
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