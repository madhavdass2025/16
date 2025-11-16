<?php
// ... (PHP code remains the same)
?>

<form id="sales-form">
    <!-- ... (form layout remains the same) -->
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const salesForm = document.getElementById('sales-form');
    // ... (other variable declarations remain the same)

    // ... (addBillingRow, calculateRow, and other functions remain the same)

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

    // ... (rest of the JavaScript remains the same)
});
</script>

<?php
// ... (PHP template code remains the same)
?>