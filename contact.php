<?php
// contact.php
// Minimal, pragmatic mail handler. Consider using a proper SMTP service for deliverability.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo "Method not allowed";
  exit;
}

function clean($v) {
  $v = trim($v ?? '');
  $v = str_replace(["\r", "\n"], ' ', $v); // prevent header injection
  return $v;
}

$name = clean($_POST['name'] ?? '');
$email = clean($_POST['email'] ?? '');
$phone = clean($_POST['phone'] ?? '');
$attending = clean($_POST['attending'] ?? '');
$message = trim($_POST['message'] ?? '');
$consent = isset($_POST['consent']);

if ($name === '' || $email === '' || $attending === '' || !$consent) {
  http_response_code(400);
  echo "Missing required fields.";
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo "Invalid email.";
  exit;
}

$to = "info@spirit.nz"; // TODO: change to your real inbox
$subject = "Beginner TKD Signup: $name ($attending)";

$body = "New signup received\n\n";
$body .= "Name: $name\n";
$body .= "Email: $email\n";
$body .= "Phone: " . ($phone ?: "(not provided)") . "\n";
$body .= "Attending: $attending\n";
$body .= "Message:\n$message\n\n";
$body .= "Submitted: " . date('c') . "\n";
$body .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";

$headers = [];
$headers[] = "From: Spirit Taekwon-Do <no-reply@spirit.nz>";
$headers[] = "Reply-To: $name <$email>";
$headers[] = "Content-Type: text/plain; charset=UTF-8";

$ok = mail($to, $subject, $body, implode("\r\n", $headers));

if ($ok) {
  header("Location: index.html#signup");
  exit;
}

http_response_code(500);
echo "Sorry — something went wrong sending your message.";
