<?php
// contact.php
// Form handler with SMTP support for better deliverability

// Load mail configuration
if (!file_exists('mail-config.php')) {
  http_response_code(500);
  echo "Mail configuration not found. Please create mail-config.php from mail-config.example.php";
  exit;
}
require_once('mail-config.php');

// Load SMTP mailer if needed
if ($USE_SMTP) {
  require_once('smtp-mailer.php');
}

/**
 * Initialize SQLite database for form submissions backup
 * Creates database and table if they don't exist
 */
function initDatabase() {
  try {
    $db = new PDO('sqlite:submissions.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create submissions table if it doesn't exist
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

    return $db;
  } catch (PDOException $e) {
    error_log("Database init error: " . $e->getMessage());
    return null;
  }
}

/**
 * Save form submission to database
 * Returns submission ID or null on failure
 */
function saveSubmission($db, $name, $email, $phone, $people, $message) {
  if (!$db) return null;

  try {
    $stmt = $db->prepare("
      INSERT INTO submissions (name, email, phone, people, message, ip_address, user_agent)
      VALUES (:name, :email, :phone, :people, :message, :ip, :ua)
    ");

    $stmt->execute([
      ':name' => $name,
      ':email' => $email,
      ':phone' => $phone,
      ':people' => $people,
      ':message' => $message,
      ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
      ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);

    return $db->lastInsertId();
  } catch (PDOException $e) {
    error_log("Database save error: " . $e->getMessage());
    return null;
  }
}

/**
 * Update submission status after emails/API complete
 */
function updateSubmissionStatus($db, $id, $adminEmailSent, $confirmEmailSent, $metaApiSent) {
  if (!$db || !$id) return;

  try {
    $stmt = $db->prepare("
      UPDATE submissions
      SET admin_email_sent = :admin,
          confirm_email_sent = :confirm,
          meta_api_sent = :meta
      WHERE id = :id
    ");

    $stmt->execute([
      ':admin' => $adminEmailSent ? 1 : 0,
      ':confirm' => $confirmEmailSent ? 1 : 0,
      ':meta' => $metaApiSent ? 1 : 0,
      ':id' => $id
    ]);
  } catch (PDOException $e) {
    error_log("Database update error: " . $e->getMessage());
  }
}

/**
 * Send conversion event to Meta Conversion API
 * @param string $email User's email address
 * @param string $phone User's phone number
 * @param string $name User's name
 * @return bool Returns true if event was sent successfully, false otherwise
 */
function sendMetaConversionEvent($email, $phone, $name) {
  global $META_PIXEL_ID, $META_ACCESS_TOKEN;

  // Skip if Meta credentials are not configured
  if (empty($META_PIXEL_ID) || empty($META_ACCESS_TOKEN) || $META_ACCESS_TOKEN === 'your-meta-access-token') {
    error_log("Meta Conversion API: Credentials not configured, skipping event");
    return false;
  }

  try {
    // Hash user data with SHA256 for privacy
    $hashedEmail = hash('sha256', strtolower(trim($email)));
    $hashedPhone = hash('sha256', preg_replace('/[^0-9]/', '', $phone)); // Remove non-numeric characters

    // Split name into first and last name (best effort)
    $nameParts = explode(' ', trim($name), 2);
    $firstName = $nameParts[0] ?? '';
    $lastName = $nameParts[1] ?? '';
    $hashedFirstName = hash('sha256', strtolower($firstName));
    $hashedLastName = hash('sha256', strtolower($lastName));

    // Get client IP and user agent
    $clientIpAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $clientUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    // Build event data payload
    $eventData = [
      'event_name' => 'Lead',
      'event_time' => time(),
      'action_source' => 'website',
      'event_source_url' => 'https://spirit.nz/beginners/',
      'user_data' => [
        'em' => [$hashedEmail],
        'ph' => [$hashedPhone],
        'fn' => [$hashedFirstName],
        'ln' => [$hashedLastName],
        'client_ip_address' => $clientIpAddress,
        'client_user_agent' => $clientUserAgent,
      ]
    ];

    // Build the full payload
    $payload = [
      'data' => [$eventData]
    ];

    // Send to Meta Conversion API
    $url = "https://graph.facebook.com/v21.0/{$META_PIXEL_ID}/events?access_token={$META_ACCESS_TOKEN}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Log the response for debugging
    if ($httpCode === 200) {
      error_log("Meta Conversion API: Lead event sent successfully - Response: " . $response);
      return true;
    } else {
      error_log("Meta Conversion API: Failed to send event - HTTP {$httpCode} - Response: " . $response);
      return false;
    }

  } catch (Exception $e) {
    error_log("Meta Conversion API error: " . $e->getMessage());
    // Don't fail the form submission if Meta API fails
    return false;
  }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo "Method not allowed";
  exit;
}

function clean($v)
{
  $v = trim($v ?? '');
  $v = str_replace(["\r", "\n"], ' ', $v); // prevent header injection
  return $v;
}

$name = clean($_POST['name'] ?? '');
$email = clean($_POST['email'] ?? '');
$phone = clean($_POST['phone'] ?? '');
$people = clean($_POST['people'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $phone === '' || $people === '') {
  http_response_code(400);
  echo "Missing required fields.";
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo "Invalid email address.";
  exit;
}

// Initialize database and save submission immediately
$db = initDatabase();
$submissionId = saveSubmission($db, $name, $email, $phone, $people, $message);

// Send Meta Conversion API event immediately (on button press, not email success)
$metaApiSuccess = sendMetaConversionEvent($email, $phone, $name);

// Prepare admin notification email
$subject = "Beginner TKD Signup: $name";

$body = "New signup received\n\n";
$body .= "Name: $name\n";
$body .= "Email: $email\n";
$body .= "Phone: $phone\n";
$body .= "Number of people: $people\n";
$body .= "Message:\n$message\n\n";
$body .= "Submitted: " . date('c') . "\n";
$body .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";

// Prepare confirmation email
$confirmSubject = $CONFIRMATION_EMAIL_SUBJECT;
$confirmBody = str_replace('{{NAME}}', $name, $CONFIRMATION_EMAIL_BODY);

$adminEmailSent = false;
$confirmEmailSent = false;

try {
  if ($USE_SMTP) {
    // Use SMTP
    $mailer = new SMTPMailer($SMTP_HOST, $SMTP_PORT, $SMTP_SECURE, $SMTP_USERNAME, $SMTP_PASSWORD);

    // Send admin notification
    $adminEmailSent = $mailer->send(
      $MAIL_TO_ADDRESS,
      $subject,
      $body,
      $MAIL_FROM_NAME,
      $MAIL_FROM_ADDRESS,
      $email,
      false
    );

    // Send confirmation email
    $confirmEmailSent = $mailer->send(
      $email,
      $confirmSubject,
      $confirmBody,
      $MAIL_FROM_NAME,
      $MAIL_FROM_ADDRESS,
      $MAIL_REPLY_TO,
      true
    );

  } else {
    // Use basic PHP mail()
    $headers = [];
    $headers[] = "From: $MAIL_FROM_NAME <$MAIL_FROM_ADDRESS>";
    $headers[] = "Reply-To: $name <$email>";
    $headers[] = "Content-Type: text/plain; charset=UTF-8";

    $adminEmailSent = mail($MAIL_TO_ADDRESS, $subject, $body, implode("\r\n", $headers));

    $confirmHeaders = [];
    $confirmHeaders[] = "From: $MAIL_FROM_NAME <$MAIL_FROM_ADDRESS>";
    $confirmHeaders[] = "Reply-To: $MAIL_REPLY_TO";
    $confirmHeaders[] = "Content-Type: text/html; charset=UTF-8";
    $confirmHeaders[] = "MIME-Version: 1.0";

    $confirmEmailSent = mail($email, $confirmSubject, $confirmBody, implode("\r\n", $confirmHeaders));
  }

  if ($adminEmailSent && $confirmEmailSent) {
    // Update database with success status
    updateSubmissionStatus($db, $submissionId, true, true, $metaApiSuccess);

    header("Location: index.html?success=1#contact");
    exit;
  }

  // Update database with partial success (if only one email sent)
  updateSubmissionStatus($db, $submissionId, $adminEmailSent, $confirmEmailSent, $metaApiSuccess);

} catch (Exception $e) {
  error_log("Email sending failed: " . $e->getMessage());
  http_response_code(500);
  echo "Sorry — something went wrong sending your message. Please try again or contact us directly.";
  exit;
}

http_response_code(500);
echo "Sorry — something went wrong sending your message.";
