<?php
/**
 * Manually add a submission to the database
 * Use this to backfill submissions that came via email before the database was implemented
 */

// Submission data
$name = "Donna Williams";
$email = "donnafw77@gmail.com";
$phone = "0211813454";
$people = "2 people - 1 adult and 1 14 year old girl";
$message = "";
$submitted_at = "2025-12-24 04:42:28";
$ip_address = "27.252.83.229";
$user_agent = "Email submission (backfilled)";

// These emails were already sent successfully
$admin_email_sent = 1;
$confirm_email_sent = 1;
$meta_api_sent = 0; // Database wasn't active, so no Meta API

try {
  $db = new PDO('sqlite:submissions.db');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Create table if it doesn't exist
  $db->exec("
    CREATE TABLE IF NOT EXISTS submissions (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      email TEXT NOT NULL,
      phone TEXT NOT NULL,
      people TEXT NOT NULL,
      message TEXT,
      submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      ip_address TEXT,
      user_agent TEXT,
      admin_email_sent INTEGER DEFAULT 0,
      confirm_email_sent INTEGER DEFAULT 0,
      meta_api_sent INTEGER DEFAULT 0
    )
  ");

  // Check if this email already exists
  $checkStmt = $db->prepare("SELECT COUNT(*) FROM submissions WHERE email = :email AND submitted_at = :submitted_at");
  $checkStmt->execute([':email' => $email, ':submitted_at' => $submitted_at]);
  $exists = $checkStmt->fetchColumn();

  if ($exists > 0) {
    echo "❌ Error: This submission already exists in the database.\n";
    exit(1);
  }

  // Insert the submission
  $stmt = $db->prepare("
    INSERT INTO submissions (
      name, email, phone, people, message, submitted_at, ip_address, user_agent,
      admin_email_sent, confirm_email_sent, meta_api_sent
    ) VALUES (
      :name, :email, :phone, :people, :message, :submitted_at, :ip, :ua,
      :admin, :confirm, :meta
    )
  ");

  $stmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':phone' => $phone,
    ':people' => $people,
    ':message' => $message,
    ':submitted_at' => $submitted_at,
    ':ip' => $ip_address,
    ':ua' => $user_agent,
    ':admin' => $admin_email_sent,
    ':confirm' => $confirm_email_sent,
    ':meta' => $meta_api_sent
  ]);

  $id = $db->lastInsertId();

  echo "✅ Success! Submission added to database.\n";
  echo "   ID: {$id}\n";
  echo "   Name: {$name}\n";
  echo "   Email: {$email}\n";
  echo "   Submitted: {$submitted_at}\n";
  echo "\n";
  echo "You can now view this in the admin panel at:\n";
  echo "https://spirit.nz/beginners/view-submissions.php\n";

} catch (PDOException $e) {
  echo "❌ Database error: " . $e->getMessage() . "\n";
  exit(1);
}
