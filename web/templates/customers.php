<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Invoice Importer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .nav { display: flex; gap: 20px; margin-top: 15px; }
        .nav a { text-decoration: none; color: #007bff; padding: 8px 16px; border-radius: 4px; transition: background 0.2s; }
        .nav a:hover, .nav a.active { background: #007bff; color: white; }
        .content { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; transition: background 0.2s; font-size: 14px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .address { color: #666; font-size: 0.9em; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice Importer - Customers</h1>
            <p>View and manage all customers</p>
            <nav class="nav">
                <a href="?action=dashboard" class="<?= $current_action === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                <a href="?action=invoices" class="<?= $current_action === 'invoices' ? 'active' : '' ?>">Invoices</a>
                <a href="?action=customers" class="<?= $current_action === 'customers' ? 'active' : '' ?>">Customers</a>
            </nav>
        </div>

        <div class="content">
            <h2>All Customers (<?= count($customers) ?>)</h2>
            
            <?php if (!empty($customers)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?= $customer->getCustomerId() ?></td>
                                <td><?= htmlspecialchars($customer->getCustomerName()) ?></td>
                                <td>
                                    <div class="address"><?= htmlspecialchars($customer->getCustomerAddress()) ?></div>
                                </td>
                                <td><?= $customer->getCreatedAt()->format('Y-m-d H:i') ?></td>
                                <td>
                                    <a href="?action=export&format=json&type=invoices&customer_id=<?= $customer->getCustomerId() ?>" class="btn btn-primary btn-sm">View Invoices</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No customers found. Import some data to get started.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
