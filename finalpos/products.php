<?php
// ... (PHP code remains the same)
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <!-- ... (HTML remains the same) -->
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productModal = new bootstrap.Modal(document.getElementById('product-modal'));
    const productForm = document.getElementById('product-form');

    document.getElementById('add-product-btn').addEventListener('click', function() {
        productForm.reset();
        document.getElementById('Mid').value = 0;
        document.getElementById('product-modal-title').textContent = 'Add Product';
    });

    document.querySelectorAll('.edit-product-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            fetch(`ajax_product_crud.php?action=get_product&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('product-modal-title').textContent = 'Edit Product';
                    document.getElementById('Mid').value = data.Mid;
                    document.getElementById('name').value = data.name;
                    document.getElementById('UnitPrice').value = data.UnitPrice;
                    document.getElementById('hsn').value = data.hsn;
                    document.getElementById('Itax').value = data.Itax;
                    document.getElementById('cess').value = data.cess;
                });
        });
    });

    productForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(productForm);
        fetch('ajax_product_crud.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                productModal.hide();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    });

    document.querySelectorAll('.delete-product-btn').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this product?')) {
                const productId = this.dataset.productId;
                const formData = new FormData();
                formData.append('action', 'delete_product');
                formData.append('Mid', productId);

                fetch('ajax_product_crud.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        });
    });
});
</script>

<?php
include 'includes/footer.php';
?>