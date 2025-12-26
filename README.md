# Spirit Taekwon-Do Landing Page

## Contact Form Setup

The contact form is now fully functional and separated from configuration.

### Initial Setup

1. **Copy the example config file:**
   ```bash
   cp mail-config.example.php mail-config.php
   ```

2. **Edit `mail-config.php` with your email and SMTP settings:**

   **Email Settings:**
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

   **Meta Pixel & Conversion API Settings:**
   - `$META_PIXEL_ID`: Your Meta Pixel ID (default: `846088281552866`)
   - `$META_ACCESS_TOKEN`: Your Meta Conversions API access token
     - Get this from [Meta Events Manager](https://business.facebook.com/events_manager2) > Settings > Conversions API
     - Click "Generate access token" and copy it here
     - If not configured, conversion tracking will be skipped (pixel still works)

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

1. User visits the page → **Meta Pixel tracks PageView**
2. User submits the form
3. **Form data saved to database immediately** (SQLite backup)
   - Ensures no data is lost even if emails fail
   - Tracks submission status for all operations
4. **Two emails are sent:**
   - **Admin notification**: Plain text email to you with all form details
   - **Confirmation email**: HTML email to the submitter with your custom message
5. **Meta Conversion API fires "Lead" event** (server-side)
   - Sends hashed user data (email, phone, name) for better ad matching
   - Works even if user has ad blockers or closes browser
   - More reliable than client-side pixel tracking
6. **Database updated with success/failure status**
7. User sees success message on the page
8. Both emails must send successfully for the form to show success

### Meta Pixel & Conversion API Tracking

This site includes **Meta (Facebook) Pixel** tracking with server-side **Conversion API** for better attribution and ad performance.

**What's tracked:**
- **PageView**: Automatically tracked when anyone visits the page (client-side pixel)
- **Lead**: Fired server-side via Conversion API when form is successfully submitted
  - Includes hashed user data: email, phone, first name, last name
  - Includes client IP and user agent for better matching
  - Event source URL and timestamp

**Setup requirements:**
1. Meta Pixel code is already in `index.html` (Pixel ID: `846088281552866`)
2. Get your Conversion API access token from [Meta Events Manager](https://business.facebook.com/events_manager2)
3. Add the token to `mail-config.php` as `$META_ACCESS_TOKEN`
4. If token is not configured, only client-side pixel tracking will work (Conversion API events will be skipped)

**Why use Conversion API:**
- iOS 14.5+ privacy changes limit pixel tracking
- Server-side events work even with ad blockers
- Better match rates and attribution
- More reliable conversion tracking

**Testing your setup:**
1. Submit a test form
2. Check your server error logs for "Meta Conversion API: Lead event sent successfully"
3. Go to [Meta Events Manager](https://business.facebook.com/events_manager2) > Test Events
4. You should see both PageView (pixel) and Lead (server) events

### Database Backup System

All form submissions are automatically saved to a **SQLite database** (`submissions.db`) as a backup, ensuring no data is lost even if emails fail.

**Features:**
- ✅ Automatic backup of all form submissions
- ✅ Tracks email delivery status (admin email, confirmation email, Meta API)
- ✅ Stores IP address and user agent for security
- ✅ Searchable and exportable data

**Viewing Submissions:**
1. Go to: `https://spirit.nz/beginners/view-submissions.php`
2. Login with password (default: `changeme123` - **CHANGE THIS!**)
3. View all submissions in a clean admin interface
4. Export to CSV for analysis in Excel/Google Sheets

**Changing the Admin Password:**
Edit `view-submissions.php` and change line 8:
```php
$ADMIN_PASSWORD = 'your-secure-password-here';
```

**Database Location:**
- File: `submissions.db` (in the same directory as contact.php)
- Automatically created on first form submission
- Gitignored for privacy (not stored in repository)
- Can be downloaded via FTP for backup

**Database Schema:**
- `id`: Auto-incrementing submission ID
- `name`, `email`, `phone`, `people`, `message`: Form fields
- `submitted_at`: Timestamp of submission
- `ip_address`, `user_agent`: Client information
- `admin_email_sent`, `confirm_email_sent`, `meta_api_sent`: Status flags (0 or 1)

**Security:**
- Database file is gitignored (never committed to repository)
- Admin viewer is password-protected
- User data is stored securely in SQLite format
- Can be deleted anytime without affecting the form

### Troubleshooting

If you're not receiving emails:
1. Check that `mail-config.php` exists and has correct settings
2. Verify your server can send mail (many shared hosts can)
3. Check spam/junk folders
4. **Check the database viewer** - submissions are saved even if emails fail
5. Consider using a transactional email service (SendGrid, Mailgun, etc.) for better deliverability
