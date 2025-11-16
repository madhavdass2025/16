<?php
// ... (PHP code remains the same)
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">New Sale / Billing</h1>
</div>

<div id="response-message" class="alert" style="display: none;"></div>

<form id="sales-form">
    <!-- ... (form layout remains the same) -->

    <button type="submit" class="btn btn-success">Save Invoice</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const salesForm = document.getElementById('sales-form');
    const addItemBtn = document.getElementById('add-item-btn');
    const billingItemsTable = document.getElementById('billing-items-table');
    const itemTemplate = document.getElementById('billing-item-template');
    const responseMessage = document.getElementById('response-message');
    let itemIndex = 0;

    // ... (addBillingRow, event listeners for product selection and removal)

    function updateTotals() {
        // ... (updateTotals logic remains the same)
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

    // Add initial row
    addBillingRow();
});
</script>

<?php
// ... (PHP template code remains the same)
?>