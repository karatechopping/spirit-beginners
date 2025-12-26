# Spirit Taekwon-Do Landing Page

## Contact Form Setup

The contact form is now fully functional and separated from configuration.

### Initial Setup

1. **Copy the example config file:**
   ```bash
   cp mail-config.example.php mail-config.php
   ```

2. **Edit `mail-config.php` with your email and SMTP settings:**
   - `$MAIL_TO_ADDRESS`: Where form submissions will be sent
   - `$MAIL_FROM_NAME`: Name shown in the "From" field
   - `$MAIL_FROM_ADDRESS`: Email address in the "From" field  
   - `$MAIL_REPLY_TO`: Where replies will go
   - `$USE_SMTP`: Set to `true` to use SMTP (recommended) or `false` for basic mail()
   - `$SMTP_HOST`: Your SMTP server (e.g., smtp.gmail.com, smtp.sendgrid.net)
   - `$SMTP_PORT`: Usually 587 for TLS or 465 for SSL
   - `$SMTP_SECURE`: Set to "tls" or "ssl"
   - `$SMTP_USERNAME`: Your SMTP username (usually your email)  
   - `$SMTP_PASSWORD`: Your SMTP password or app-specific password
   - `$CONFIRMATION_EMAIL_SUBJECT`: Subject line for confirmation emails
   - `$CONFIRMATION_EMAIL_BODY`: HTML content for confirmation emails

   **For Gmail:**
   - Use `smtp.gmail.com`, port `587`, secure `tls`
   - Create an [App Password](https://myaccount.google.com/apppasswords) instead of your regular password
   - Enable 2-factor authentication first (required for app passwords)

3. **Customize the confirmation email:**
   - The email body supports simple HTML tags
   - Use `{{NAME}}` as a placeholder - it will be replaced with the submitter's name
   - Example: `<p>Hi {{NAME}},</p>` becomes `<p>Hi John Smith,</p>`

4. **Test the form:**
   - Fill out the contact form on the website
   - Check that emails are being received
   - Verify the success message appears after submission

### Security Notes

- `mail-config.php` is gitignored to keep your email settings private
- Never commit `mail-config.php` to version control
- The example file (`mail-config.example.php`) is safe to commit

### Files

- `contact.php` - Form handler (processes submissions)
- `mail-config.php` - Your email settings (gitignored, create from example)
- `mail-config.example.php` - Template for mail config
- `index.html` - Landing page with contact form

### Form Fields

The form collects:
- Name (required)
- Email (required) - used to send confirmation email
- Phone number (required)
- Number of people and ages (required)
- Additional message (optional)

### How It Works

1. User submits the form
2. **Two emails are sent:**
   - **Admin notification**: Plain text email to you with all form details
   - **Confirmation email**: HTML email to the submitter with your custom message
3. User sees success message on the page
4. Both emails must send successfully for the form to show success

### Troubleshooting

If you're not receiving emails:
1. Check that `mail-config.php` exists and has correct settings
2. Verify your server can send mail (many shared hosts can)
3. Check spam/junk folders
4. Consider using a transactional email service (SendGrid, Mailgun, etc.) for better deliverability
