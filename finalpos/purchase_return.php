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
        <h1 class="h2">Purchase Return</h1>
    </div>

    <div id="response-message" class="alert" style="display: none;"></div>

    <form id="purchase-return-form">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="purchase_invoice_search" class="form-label">Search Purchase Invoice #</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="purchase_invoice_search">
                        <button class="btn btn-outline-secondary" type="button" id="find-invoice-btn">Find</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="return_date" class="form-label">Return Date</label>
                    <input type="date" class="form-control" id="return_date" name="return_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
        </div>

        <input type="hidden" name="purchase_id" id="purchase_id">

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Batch</th>
                        <th>Purchased Qty</th>
                        <th>Return Qty</th>
                    </tr>
                </thead>
                <tbody id="return-items-table">
                </tbody>
            </table>
        </div>

        <button type="submit" class="btn btn-primary">Process Return</button>
    </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const findInvoiceBtn = document.getElementById('find-invoice-btn');
    const returnItemsTable = document.getElementById('return-items-table');
    const purchaseIdInput = document.getElementById('purchase_id');
    const returnForm = document.getElementById('purchase-return-form');
    const responseMessage = document.getElementById('response-message');

    findInvoiceBtn.addEventListener('click', function() {
        const invoiceNumber = document.getElementById('purchase_invoice_search').value;
        if (!invoiceNumber) return;

        fetch(`ajax_purchase_return.php?action=get_invoice_items&invoice_number=${invoiceNumber}`)
            .then(response => response.json())
            .then(data => {
                let tableHtml = '';
                if (data.length > 0) {
                    purchaseIdInput.value = data[0].purchase_id;
                    data.forEach(item => {
                        tableHtml += `
                            <tr>
                                <td>${item.product_name}</td>
                                <td>${item.batch_number}</td>
                                <td>${item.quantity_purchased}</td>
                                <td>
                                    <input type="hidden" name="items[${item.p_item_id}][batch_id]" value="${item.batch_id}">
                                    <input type="number" class="form-control" name="items[${item.p_item_id}][quantity_returned]" min="0" max="${item.quantity_purchased}" value="0">
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    tableHtml = '<tr><td colspan="4" class="text-center">Invoice not found or has no items.</td></tr>';
                }
                returnItemsTable.innerHTML = tableHtml;
            });
    });

    returnForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(returnForm);

        fetch('ajax_purchase_return.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            responseMessage.style.display = 'block';
            if (data.status === 'success') {
                responseMessage.className = 'alert alert-success';
                responseMessage.textContent = data.message;
                returnForm.reset();
                returnItemsTable.innerHTML = '';
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