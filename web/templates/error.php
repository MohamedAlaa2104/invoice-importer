<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Invoice Importer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; }
        .container { max-width: 800px; margin: 50px auto; padding: 20px; }
        .error-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .error-icon { font-size: 4em; color: #dc3545; margin-bottom: 20px; }
        .error-title { font-size: 1.5em; color: #dc3545; margin-bottom: 15px; }
        .error-message { color: #666; margin-bottom: 30px; line-height: 1.6; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; transition: background 0.2s; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-card">
            <div class="error-icon">⚠️</div>
            <h1 class="error-title">Error</h1>
            <p class="error-message"><?= htmlspecialchars($message) ?></p>
            <a href="?action=dashboard" class="btn btn-primary">Return to Dashboard</a>
        </div>
    </div>
</body>
</html>
