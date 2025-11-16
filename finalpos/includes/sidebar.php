<?php
// Define navigation items and the roles that can access them
$nav_items = [
    'Dashboard' => ['href' => 'dashboard.php', 'icon' => 'home', 'roles' => ['Admin', 'Manager', 'Cashier']],
    'Users' => ['href' => 'users.php', 'icon' => 'users', 'roles' => ['Admin']],
    'Customers' => ['href' => 'customers.php', 'icon' => 'users', 'roles' => ['Admin', 'Manager', 'Cashier']],
    'Categories' => ['href' => 'categories.php', 'icon' => 'tag', 'roles' => ['Admin', 'Manager']],
    'Products' => ['href' => 'products.php', 'icon' => 'shopping-cart', 'roles' => ['Admin', 'Manager']],
    'Suppliers' => ['href' => 'suppliers.php', 'icon' => 'truck', 'roles' => ['Admin', 'Manager']],
    'Inventory' => ['href' => 'inventory.php', 'icon' => 'package', 'roles' => ['Admin', 'Manager']],
    'Stock Adjustments' => ['href' => 'stock_adjustments.php', 'icon' => 'tool', 'roles' => ['Admin', 'Manager']],
    'Purchase' => ['href' => 'purchase_entry.php', 'icon' => 'file-text', 'roles' => ['Admin', 'Manager']],
    'Sales' => ['href' => 'sales.php', 'icon' => 'file', 'roles' => ['Admin', 'Manager', 'Cashier']],
    'Reports' => ['href' => 'reports.php', 'icon' => 'bar-chart-2', 'roles' => ['Admin', 'Manager']],
];

$current_role = $_SESSION['role_name'] ?? '';
?>
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <?php foreach ($nav_items as $name => $item): ?>
                <?php if (in_array($current_role, $item['roles'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $item['href']; ?>">
                            <span data-feather="<?php echo $item['icon']; ?>"></span>
                            <?php echo $name; ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>

        <hr>
        <div class="px-3">
             <a href="logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
    </div>
</nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
      feather.replace()
    </script>
