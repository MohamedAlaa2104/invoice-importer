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
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; margin-top: 5px; }
        .recent-section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .export-buttons { margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; transition: background 0.2s; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #1e7e34; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice Importer Dashboard</h1>
            <p>Manage and export your invoice data</p>
            <nav class="nav">
                <a href="?action=dashboard" class="<?= $current_action === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                <a href="?action=invoices" class="<?= $current_action === 'invoices' ? 'active' : '' ?>">Invoices</a>
                <a href="?action=customers" class="<?= $current_action === 'customers' ? 'active' : '' ?>">Customers</a>
            </nav>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= number_format($total_customers) ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($total_invoices) ?></div>
                <div class="stat-label">Total Invoices</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?= number_format($total_revenue, 2) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <div class="recent-section">
            <h2>Recent Invoices</h2>
            <?php if (!empty($recent_invoices)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_invoices as $invoice): ?>
                            <tr>
                                <td><?= htmlspecialchars($invoice->getInvoiceNumber()) ?></td>
                                <td><?= htmlspecialchars($invoice->getCustomer() ? $invoice->getCustomer()->getCustomerName() : 'Unknown') ?></td>
                                <td><?= $invoice->getInvoiceDate()->format('Y-m-d') ?></td>
                                <td>$<?= number_format($invoice->getGrandTotal(), 2) ?></td>
                                <td>
                                    <a href="?action=export&format=json&type=invoices&invoice_id=<?= $invoice->getInvoiceId() ?>" class="btn btn-primary">Export</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No invoices found. Import some data to get started.</p>
            <?php endif; ?>
        </div>

        <div class="export-buttons">
            <h3>Quick Export</h3>
            <a href="?action=export&format=json&type=invoices" class="btn btn-primary">Export All Invoices (JSON)</a>
            <a href="?action=export&format=xml&type=invoices" class="btn btn-primary">Export All Invoices (XML)</a>
            <a href="?action=export&format=json&type=customers" class="btn btn-success">Export All Customers (JSON)</a>
            <a href="?action=export&format=xml&type=customers" class="btn btn-success">Export All Customers (XML)</a>
        </div>
    </div>
</body>
</html>
