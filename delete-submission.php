<?php
/**
 * Delete a specific submission from the database by ID
 * Usage: php delete-submission.php
 */

// ID of the submission to delete
$submissionId = 2; // Change this to the ID you want to delete

try {
  $db = new PDO('sqlite:submissions.db');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // First, fetch the submission details so we can show what's being deleted
  $stmt = $db->prepare("SELECT * FROM submissions WHERE id = :id");
  $stmt->execute([':id' => $submissionId]);
  $submission = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$submission) {
    echo "❌ Error: No submission found with ID {$submissionId}\n";
    exit(1);
  }

  // Show what we're about to delete
  echo "Found submission to delete:\n";
  echo "  ID: {$submission['id']}\n";
  echo "  Name: {$submission['name']}\n";
  echo "  Email: {$submission['email']}\n";
  echo "  Submitted: {$submission['submitted_at']}\n";
  echo "\n";

  // Delete the submission
  $deleteStmt = $db->prepare("DELETE FROM submissions WHERE id = :id");
  $deleteStmt->execute([':id' => $submissionId]);

  echo "✅ Successfully deleted submission ID {$submissionId}\n";

} catch (PDOException $e) {
  echo "❌ Database error: " . $e->getMessage() . "\n";
  exit(1);
}
