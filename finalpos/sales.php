<?php
// ... (PHP code remains the same)
?>
<main class="col-12">
    <!-- ... (HTML layout remains the same) -->
</main>

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