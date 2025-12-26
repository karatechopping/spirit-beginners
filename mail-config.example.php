<?php
/**
 * Mail Configuration
 * 
 * Copy this file to mail-config.php and update with your actual email settings.
 * The mail-config.php file is gitignored for security.
 */

// Email address where form submissions will be sent
$MAIL_TO_ADDRESS = "info@spirit.nz";

// From address for outgoing emails
$MAIL_FROM_NAME = "Spirit Taekwon-Do Website";
$MAIL_FROM_ADDRESS = "no-reply@spirit.nz";

// Reply-to address (where replies will go)
$MAIL_REPLY_TO = "info@spirit.nz";

// SMTP Settings (for better deliverability)
$USE_SMTP = true; // Set to false to use basic PHP mail() instead
$SMTP_HOST = "smtp.hostinger.com"; // e.g., smtp.gmail.com, smtp.sendgrid.net, etc.
$SMTP_PORT = 465; // Usually 587 for TLS or 465 for SSL
$SMTP_SECURE = "ssl"; // "tls" or "ssl"
$SMTP_USERNAME = "brett@spirit.net"; // Your SMTP username
$SMTP_PASSWORD = "your-app-password"; // Your SMTP password or app password

// Confirmation email content (sent to people who submit the form)
// You can use simple HTML tags like <p>, <strong>, <br>, etc.
$CONFIRMATION_EMAIL_SUBJECT = "Thanks for Registering - Free Beginner Classes";
$CONFIRMATION_EMAIL_BODY = "
<p>Hi {{NAME}},</p>

<p><strong>Thank you for registering for our free beginner Taekwon-Do classes!</strong></p>

<p>We've received your registration and will be in touch within 24 hours to confirm your spot and answer any questions.</p>

<p>In the meantime, here's what you need to know:</p>

<p><strong>What to bring:</strong></p>
<ul>
  <li>Exercise clothes (tracksuit or similar)</li>
  <li>Bare feet (we train without shoes)</li>
  <li>A water bottle</li>
  <li>A smile!</li>
</ul>

<p><strong>Class Details:</strong><br>
📅 Starting: 7th January 2026, then the following Fri, Wed, Fri.<br>
⏰ Time: 6:00pm - 7:00pm<br>
📍 Location: Newlands Centennial Hall, Wellington<br>
💰 Cost: FREE</p>

<p>If you have any questions before then, feel free to reply to this email or call Brett at 027 333 4240.</p>

<p>Looking forward to seeing you on the floor!</p>

<p>Best regards,<br>
<strong>Brett Kraiger, Spirit Taekwon-Do</strong></p>
";
