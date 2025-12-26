<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delete Submission</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    body {
      padding: 2rem;
    }

    .success {
      background: #d4edda;
      color: #155724;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
    }

    .error {
      background: #f8d7da;
      color: #721c24;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
    }

    .info {
      background: #fff3cd;
      color: #856404;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
    }

    .submission-details {
      background: #f8f9fa;
      padding: 1rem;
      border-radius: 8px;
      margin: 1rem 0;
      border: 1px solid #dee2e6;
    }

    .btn-danger {
      background: #dc3545;
      color: white;
    }

    .btn-danger:hover {
      background: #c82333;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    .container {
      max-width: 600px;
      margin: 0 auto;
    }
  </style>
</head>

<body>
  <div class="container">
    <h1>Delete Submission</h1>

    <?php
    $message = '';
    $messageType = '';
    $submission = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $submissionId = $_POST['id'] ?? '';

      if (empty($submissionId) || !is_numeric($submissionId)) {
        $message = 'Please provide a valid numeric submission ID';
        $messageType = 'error';
      } else {
        try {
          $db = new PDO('sqlite:submissions.db');
          $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

          // Fetch the submission details
          $stmt = $db->prepare("SELECT * FROM submissions WHERE id = :id");
          $stmt->execute([':id' => $submissionId]);
          $submission = $stmt->fetch(PDO::FETCH_ASSOC);

          if (!$submission) {
            $message = "No submission found with ID {$submissionId}";
            $messageType = 'error';
          } elseif (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
            // Delete confirmed
            $deleteStmt = $db->prepare("DELETE FROM submissions WHERE id = :id");
            $deleteStmt->execute([':id' => $submissionId]);

            $message = "Successfully deleted submission ID {$submissionId}";
            $messageType = 'success';
            $submission = null; // Clear submission after delete
          }
        } catch (PDOException $e) {
          $message = "Database error: " . $e->getMessage();
          $messageType = 'error';
        }
      }
    }

    // Display message
    if ($message) {
      echo "<div class='{$messageType}'>{$message}</div>";
    }

    // Show submission details and confirm delete
    if ($submission) {
      echo "<div class='info'>⚠️ Are you sure you want to delete this submission?</div>";
      echo "<div class='submission-details'>";
      echo "<strong>ID:</strong> {$submission['id']}<br>";
      echo "<strong>Name:</strong> {$submission['name']}<br>";
      echo "<strong>Email:</strong> {$submission['email']}<br>";
      echo "<strong>Phone:</strong> {$submission['phone']}<br>";
      echo "<strong>People:</strong> {$submission['people']}<br>";
      if (!empty($submission['message'])) {
        echo "<strong>Message:</strong> {$submission['message']}<br>";
      }
      echo "<strong>Submitted:</strong> {$submission['submitted_at']}<br>";
      echo "</div>";

      echo "<form method='POST' style='margin-top: 1rem;'>";
      echo "<input type='hidden' name='id' value='{$submission['id']}'>";
      echo "<input type='hidden' name='confirm' value='yes'>";
      echo "<button type='submit' class='btn btn-danger'>Yes, Delete This Submission</button> ";
      echo "<a href='delete-submission.php' class='btn' style='background: #6c757d; color: white; text-decoration: none; display: inline-block; padding: 0.5rem 1rem; border-radius: 4px;'>Cancel</a>";
      echo "</form>";
    } else {
      // Show form to enter ID
      ?>
      <form method="POST">
        <div class="form-group">
          <label for="id">Enter Submission ID to Delete:</label>
          <input type="number" id="id" name="id" class="form-control" required min="1"
            style="width: 100%; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 4px;">
        </div>
        <button type="submit" class="btn btn-primary">Find Submission</button>
      </form>

      <p style="margin-top: 2rem;"><a href="view-submissions.php">← Back to View Submissions</a></p>
      <?php
    }
    ?>
  </div>
</body>

</html>