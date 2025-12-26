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
    header("Location: index.html?success=1#contact");
    exit;
  }

} catch (Exception $e) {
  error_log("Email sending failed: " . $e->getMessage());
  http_response_code(500);
  echo "Sorry — something went wrong sending your message. Please try again or contact us directly.";
  exit;
}

http_response_code(500);
echo "Sorry — something went wrong sending your message.";
