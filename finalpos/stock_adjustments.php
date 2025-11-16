<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager']);
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Stock Adjustments</h1>
    </div>

    <div id="response-message" class="alert" style="display: none;"></div>

    <form id="stock-adjustment-form">
        <input type="hidden" name="action" value="adjust_stock">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="product_search" class="form-label">Search Product</label>
                    <input type="text" class="form-control" id="product_search">
                    <div id="search-results" class="list-group"></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="batch_id" class="form-label">Select Batch</label>
                    <select class="form-select" id="batch_id" name="batch_id" required>
                        <option value="">Select a product first</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label">Current Quantity</label>
                    <input type="text" class="form-control" id="current_qty" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="new_qty" class="form-label">New Quantity</label>
                    <input type="number" class="form-control" id="new_qty" name="new_qty" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="reason" class="form-label">Reason</label>
                    <input type="text" class="form-control" id="reason" name="reason" required>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Adjust Stock</button>
    </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSearch = document.getElementById('product_search');
    const searchResults = document.getElementById('search-results');
    const batchSelect = document.getElementById('batch_id');
    const currentQtyInput = document.getElementById('current_qty');
    const adjustmentForm = document.getElementById('stock-adjustment-form');
    const responseMessage = document.getElementById('response-message');

    productSearch.addEventListener('keyup', function() {
        const term = productSearch.value;
        if (term.length < 2) {
            searchResults.innerHTML = '';
            return;
        }

        fetch(`ajax_stock_adjustment.php?action=search_products&term=${term}`)
            .then(response => response.json())
            .then(data => {
                let html = '';
                data.forEach(product => {
                    html += `<a href="#" class="list-group-item list-group-item-action select-product" data-product-id="${product.product_id}">${product.product_name}</a>`;
                });
                searchResults.innerHTML = html;
            });
    });

    searchResults.addEventListener('click', function(e) {
        e.preventDefault();
        if (e.target.classList.contains('select-product')) {
            const productId = e.target.dataset.productId;
            productSearch.value = e.target.textContent;
            searchResults.innerHTML = '';

            fetch(`ajax_stock_adjustment.php?action=get_batches&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    let optionsHtml = '<option value="">Select a batch</option>';
                    data.forEach(batch => {
                        optionsHtml += `<option value="${batch.batch_id}" data-current-qty="${batch.current_qty}">${batch.batch_number} (Qty: ${batch.current_qty})</option>`;
                    });
                    batchSelect.innerHTML = optionsHtml;
                });
        }
    });

    batchSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        currentQtyInput.value = selectedOption.dataset.currentQty || '';
    });

    adjustmentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(adjustmentForm);
        fetch('ajax_stock_adjustment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            responseMessage.style.display = 'block';
            if (data.status === 'success') {
                responseMessage.className = 'alert alert-success';
                responseMessage.textContent = data.message;
                adjustmentForm.reset();
                batchSelect.innerHTML = '<option value="">Select a product first</option>';
                currentQtyInput.value = '';
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