<?php
/**
 * Admin viewer for form submissions - Kelburn
 * Password-protected page to view all contact form submissions for Kelburn club
 */

// Configuration
$ADMIN_PASSWORD = 'changeme123'; // CHANGE THIS PASSWORD!

// Check authentication
session_start();

if (isset($_POST['password'])) {
    if ($_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['admin_authenticated'] = true;
    } else {
        $error = "Invalid password";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: view-kelburn.php");
    exit;
}

// Require authentication
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - Kelburn Submissions</title>
        <style>
            * {
                box-sizing: border-box;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                padding: 20px;
            }

            .login-box {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                max-width: 400px;
                width: 100%;
            }

            h1 {
                margin: 0 0 30px 0;
                font-size: 24px;
                color: #333;
            }

            input[type="password"] {
                width: 100%;
                padding: 12px;
                border: 2px solid #ddd;
                border-radius: 6px;
                font-size: 16px;
                margin-bottom: 20px;
            }

            button {
                width: 100%;
                padding: 12px;
                background: #667eea;
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                cursor: pointer;
                font-weight: 600;
            }

            button:hover {
                background: #5568d3;
            }

            .error {
                background: #fee;
                border: 1px solid #fcc;
                color: #c33;
                padding: 10px;
                border-radius: 6px;
                margin-bottom: 20px;
            }
        </style>
    </head>

    <body>
        <div class="login-box">
            <h1>🔒 Kelburn Admin Login</h1>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter password" required autofocus>
                <button type="submit">Login</button>
            </form>
        </div>
    </body>

    </html>
    <?php
    exit;
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        $db = new PDO('sqlite:submissions-kelburn.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->query("SELECT * FROM submissions ORDER BY id DESC");
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=submissions-kelburn-' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');

        // Write headers
        if (!empty($submissions)) {
            fputcsv($output, array_keys($submissions[0]));

            // Write data
            foreach ($submissions as $row) {
                fputcsv($output, $row);
            }
        }

        fclose($output);
        exit;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Load submissions from database
try {
    $db = new PDO('sqlite:submissions-kelburn.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->query("SELECT * FROM submissions ORDER BY id DESC");
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalCount = count($submissions);
    $emailFailures = array_filter($submissions, function ($s) {
        return $s['admin_email_sent'] == 0 || $s['confirm_email_sent'] == 0;
    });
    $emailFailureCount = count($emailFailures);

} catch (PDOException $e) {
    $dbError = "Database error: " . $e->getMessage();
    $submissions = [];
    $totalCount = 0;
    $emailFailureCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelburn Submissions - Admin</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }

        h1 {
            margin: 0;
            color: #333;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: #48bb78;
            color: white;
        }

        .btn-secondary:hover {
            background: #38a169;
        }

        .btn-logout {
            background: #f56565;
            color: white;
        }

        .btn-logout:hover {
            background: #e53e3e;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
        }

        .stat-card.warning {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        th,
        td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-success {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-failure {
            background: #fed7d7;
            color: #742a2a;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .error-message {
            background: #fed7d7;
            border: 1px solid #fc8181;
            color: #742a2a;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📋 Kelburn Submissions</h1>
            <div class="actions">
                <?php if ($totalCount > 0): ?>
                    <a href="?export=csv" class="btn btn-secondary">📥 Export CSV</a>
                <?php endif; ?>
                <a href="?logout" class="btn btn-logout">Logout</a>
            </div>
        </div>

        <?php if (isset($dbError)): ?>
            <div class="error-message"><?= htmlspecialchars($dbError) ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-label">Total Submissions</div>
                <div class="stat-value"><?= $totalCount ?></div>
            </div>
            <?php if ($emailFailureCount > 0): ?>
                <div class="stat-card warning">
                    <div class="stat-label">Email Failures</div>
                    <div class="stat-value"><?= $emailFailureCount ?></div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($submissions)): ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                    </path>
                </svg>
                <h2>No submissions yet</h2>
                <p>Form submissions will appear here once someone fills out the contact form.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date/Time</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>People</th>
                            <th>Message</th>
                            <th>Admin Email</th>
                            <th>Confirm Email</th>
                            <th>Meta API</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td><?= htmlspecialchars($sub['id']) ?></td>
                                <td><?= htmlspecialchars($sub['submitted_at']) ?></td>
                                <td><strong><?= htmlspecialchars($sub['name']) ?></strong></td>
                                <td><?= htmlspecialchars($sub['email']) ?></td>
                                <td><?= htmlspecialchars($sub['phone']) ?></td>
                                <td><?= htmlspecialchars($sub['people']) ?></td>
                                <td><?= htmlspecialchars(substr($sub['message'], 0, 50)) ?><?= strlen($sub['message']) > 50 ? '...' : '' ?>
                                </td>
                                <td>
                                    <span
                                        class="status-badge <?= $sub['admin_email_sent'] ? 'status-success' : 'status-failure' ?>">
                                        <?= $sub['admin_email_sent'] ? '✓ Sent' : '✗ Failed' ?>
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="status-badge <?= $sub['confirm_email_sent'] ? 'status-success' : 'status-failure' ?>">
                                        <?= $sub['confirm_email_sent'] ? '✓ Sent' : '✗ Failed' ?>
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="status-badge <?= $sub['meta_api_sent'] ? 'status-success' : 'status-failure' ?>">
                                        <?= $sub['meta_api_sent'] ? '✓ Sent' : '✗ Failed' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>