<?php
// ... (PHP code remains the same)
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ... (all other JavaScript remains the same)

    salesForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(salesForm);

        fetch('ajax_sales_entry.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = `bill_print.php?invoice_id=${data.invoice_id}`;
            } else {
                responseMessage.style.display = 'block';
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