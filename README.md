# BackupPilot — WordPress Backup Plugin

Schedule complete automatic backups of your WordPress installation and push them to an external storage service.

**BackupPilot is a fork of the excellent [BackWPup](https://wordpress.org/plugins/backwpup/) plugin (GPLv2+) by Inpsyde, rebranded and maintained independently.**

---

## Features

- Database backup *(requires mysqli)*
- WordPress XML export
- Generate a list of installed plugins
- Check and repair database
- File backup
- Backup archives in zip, tar, tar.gz format *(requires ZipArchive or gz)*
- Store backups to a local folder
- Store backups to an FTP server *(requires ftp)*
- Store backups to Dropbox *(requires curl)*
- Store backups to S3-compatible services *(requires curl)*
- Store backups to Microsoft Azure Blob *(requires curl)*
- Store backups to SugarSync *(requires curl)*
- Send backups to an email adress *(not recommended, but maybe useful for some edge cases)*
- Send logs by email
- Multisite support (network admin only)

## Requirements

- **WordPress** 6.0 or later
- **PHP** 8.1 or later
- For full functionality: PHP extensions `mysqli`, `ftp`, `gz`, `bz2`, `ZipArchive`, `curl`

Features that cannot run due to your server configuration will not appear in the admin area.

## Installation

Upload and activate the plugin like any other WordPress plugin, then go to **BackupPilot → Settings** to configure your first backup job.

---

## Frequently Asked Questions

### How do I restore a backup?

Extract the downloaded `.zip` archive, upload its contents back to your server, then import the included database dump.

### My backup jobs don't run as scheduled.

BackupPilot uses WordPress' own WP-Cron system. WP-Cron only ticks when your site is visited. On low-traffic sites, set up a real server-side cron job to call `wp-cron.php` at regular intervals.

To test whether WP-Cron works: create a post scheduled 10 minutes in the future, leave the site, come back after 11 minutes — if the post wasn't published automatically, WP-Cron is not firing reliably.

### I get: "ERROR: No destination correctly defined for backup!"

A backup job ran but has no storage destination configured. Cancel the running job, re-edit it, and make sure a **To: …** destination tab is properly filled in.

### A backup job started but nothing is happening.

**Option 1** — Check server self-connect:
1. Go to **BackupPilot → Settings → Information**
2. Find *Server self connect* — if it shows a `401` error, go to the **Network** tab and enter your server authentication credentials.

**Option 2** — Add this line to `wp-config.php` (before the `if (!defined('ABSPATH'))` line):
```php
define( 'ALTERNATE_WP_CRON', true );
```

### I get: "The HTTP response test returned a Connection time-out error"

Possible causes:
- Your host does not allow loopback connections
- Your WordPress or backup directory requires HTTP authentication (configure in **Settings → Network**)
- The server cannot resolve its own hostname
- A plugin or theme is blocking the request

### Fatal error: "Can not create folder: …/wp-content/backuppilot-…-logs"

Set `CHMOD 775` on `/wp-content/` and reload the BackupPilot dashboard. Try `777` if `775` doesn't work — you can revert to `755` once the folder has been created.

### The Files tab loads forever when editing a job.

Go to **Settings → General** and disable *Display folder sizes on files tab*. Calculating folder sizes can be slow on large sites.

### Can I cancel a running backup job via FTP?

Yes. Navigate to the BackupPilot temp directory and delete the file named `backuppilot-xyz-working.json` (where `xyz` is a random hash).

### Can I move the temp directory?

Yes. Add this to `wp-config.php` before the `if (!defined('ABSPATH'))` line:
```php
define( 'WP_TEMP_DIR', '/absolute/path/to/your/temp-dir' );
```

### What do the filename placeholders mean?

| Placeholder | Value |
|-------------|-------|
| `%d` | Day of month with leading zero (01–31) |
| `%j` | Day of month without leading zero (1–31) |
| `%m` | Month with leading zero (01–12) |
| `%n` | Month without leading zero (1–12) |
| `%Y` | Four-digit year |
| `%y` | Two-digit year |
| `%a` | Lowercase am / pm |
| `%A` | Uppercase AM / PM |
| `%g` | Hour, 12-hour format, no leading zero |
| `%G` | Hour, 24-hour format, no leading zero |
| `%h` | Hour, 12-hour format, with leading zero |
| `%H` | Hour, 24-hour format, with leading zero |
| `%i` | Minutes (00–59) |
| `%s` | Seconds (00–59) |

---

## Email delivery

BackupPilot sends backup archives by email using WordPress's built-in `wp_mail()` function. This means email delivery is fully controlled by your WordPress configuration — no separate SMTP settings inside BackupPilot.

To configure how emails are sent (SMTP server, API-based delivery, authentication), install one of the widely-used WordPress mail plugins:

- [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/)
- [FluentSMTP](https://wordpress.org/plugins/fluent-smtp/)
- [Post SMTP](https://wordpress.org/plugins/post-smtp/)

This approach gives you a single place to manage all outgoing email from your WordPress site, rather than configuring SMTP credentials separately in every plugin that needs to send mail.

---

## Changelog

### 1.0.2

**Dependencies**
- Removed: `swiftmailer/swiftmailer` — SwiftMailer was officially abandoned in November 2021 and receives no further security patches. Email delivery is now handled entirely by WordPress's native `wp_mail()` function, delegating transport to whatever mail configuration the site already has in place (WP Mail SMTP, FluentSMTP, Post SMTP, or WordPress's built-in PHP mail). The per-job "Sending method" configuration (SMTP host/port/credentials, Sendmail path) has been removed from the Email destination settings accordingly, giving site owners a single place to manage all outgoing mail rather than duplicating transport settings inside BackupPilot.

**Email destination improvements**
- Archive readability is verified before sending; a clear error with the full file path is logged if the file cannot be found
- PHP memory headroom is requested upfront before attaching large archives, matching the safety margin the former SwiftMailer code applied
- The file-size limit error now includes the actual archive size and the configured limit
- Mail send failures are captured via `wp_mail_failed` and logged with the PHPMailer error detail, making MTA-level rejections (e.g. Postfix message size limit) diagnosable from the job log

---

### 1.0.1

**Security**
- Escaped role slug and display name in the BackupPilot role dropdown on user profile pages (`esc_attr`/`esc_html`)
- Escaped log filename in the title attribute of the log list view action link (`esc_attr`)
- Admin notice messages are now passed through `wp_kses_post()` to prevent stored XSS via crafted notice content
- Job name is now escaped with `esc_html()` when written into the log file's HTML `<title>` (stored XSS)
- Added `current_user_can()` + nonce check to the backup file download AJAX handler before any output, closing an auth bypass
- `$_POST['anchor']` is sanitized with `sanitize_html_class()` before being appended to the redirect URL (header injection)

**Bug fixes**
- Fatal error in cron log-compression when the log directory is unreadable — catch block called a method on a non-object; falls back to `error_log()` now
- Null dereference when `get_destination()` returns `null` in the download handler; early return added
- Null dereference when deleting a backup with a malformed destination string (no underscore); `explode()` now called with limit 2
- PHP 8 `TypeError` in bulk-delete when `$_GET['backupfiles']` is not an array
- PHP 8 `TypeError` in folder downloader destructor when file handles were never opened; `fclose()` calls now guarded with `is_resource()`
- Dead `try/catch` around `fopen()` in folder downloader replaced with an `is_resource()` check
- PHP 8 undefined key notice in `save_post_form()` when the `page` parameter is missing from `$_POST`
- Log entries with identical modification timestamps silently overwrote each other; array key now includes the filename
- `get_job()` silently returned defaults for deleted/non-existent job IDs instead of `false`
- `file_download()` ignored its validated `$jobid` parameter and re-read `$_GET['jobid']` directly

---

### 1.0.0 — 2026-06-24

**Fork & rebrand**
- Forked from BackWPup 3.10.0 and rebranded as BackupPilot (classes, hooks, options, capabilities, roles and asset identifiers renamed)
- One-time automatic migration of settings, jobs and user roles from an existing BackWPup installation on activation
- Removed all Pro-tier code paths and related autoloader entries
- Removed promotional content, upsell links and all backwpup.com references
- Reconstructed `composer.json` to reflect the actually bundled vendor dependencies

**Security**
- Fixed path traversal in the log folder setting that allowed the log directory to be placed outside the WordPress content directory
- Switched folder/archive hash generation from `mt_rand()` to `random_int()` for cryptographically secure output
- Added nginx admin warning (with config snippet) since `.htaccess` folder protection has no effect on nginx
- Dropbox OAuth access and refresh tokens are now encrypted at rest
- FTP destination no longer exposes the decrypted password in download URLs; requests are now routed through the internal secure download proxy
- Added missing capability check on the email destination's "send test email" AJAX handler
- Restricted the sendmail command setting to a safe path/flags pattern to prevent shell injection via Swift_SendmailTransport
- Closed an unauthenticated remote job trigger: the `cronrun` start type was reachable via a plain query parameter with its nonce check disabled; it is now restricted to WordPress's own scheduled cron execution

**Bug fixes**
- DB Check job no longer misclassifies InnoDB tables as failed (only the final status row of a multi-row `CHECK TABLE` result is now evaluated)
- Zip format option no longer disabled in the UI when `ZipArchive` is absent — the backend already falls back to PclZip
- Corrected wrong `sanitize_email()` applied to the sendmail path field
- Replaced deprecated `FILTER_SANITIZE_STRING` usages with `sanitize_text_field()` (fatal on PHP 8.2+); removed two orphaned helper functions with no callers
- Fixed bundled shared vendor asset paths broken during rebrand (`functions.min.js` / `states.min.js` failed to load on the Backups page)

**Dependencies**
- Raised minimum PHP from 5.6 to **8.1**
- `phpseclib` 2.0.31 → 2.0.55
- `symfony/options-resolver` 3.4.47 → 6.4.30 LTS (+ `symfony/deprecation-contracts`)
- `microsoft/azure-storage-blob` 1.4.0 → 1.5.4, `azure-storage-common` 1.5.1 → 1.5.2
- `aws/aws-sdk-php` 3.93.7 → 3.135.0 (+ Retry, Arn, Multipart subsystems)
- `guzzlehttp/guzzle` 6.5.5 → 7.8.2 (pinned; newer releases require incompatible `promises` 2.x)
- `guzzlehttp/psr7` 1.8.2 → 1.9.1, `promises` 1.4.1 → 1.5.3, `mtdowling/jmespath.php` 2.6.0 → 2.9.1
- `psr/log` 1.1.4 → 3.0.2; `symfony/polyfill-*` packages updated to 1.38.x
- Added `psr/http-client`; bumped `psr/http-message` 1.0.1 → 1.1 (Guzzle 7 requirement)

**Removed dependencies**
- Rackspace Cloud Files destination + its abandoned dependency chain (`rackspace/php-opencloud`, legacy `guzzle/guzzle` v3, `mikemccabe/json-patch-php`, `symfony/event-dispatcher`)
- `krizalys/onedrive-php-sdk`, `microsoft/microsoft-graph` (Pro-only, no consumers)
- `symfony/translation` (orphaned, no consumers)
- `pear/archive_tar` + chain (`pear-core-minimal`, `console_getopt`, `pear_exception`) — never called by any code path; also removes the historical Archive_Tar CVE-2020-36193 exposure
- `monolog/monolog`, `pimple/pimple`, `psr/container` (~540 KB of unreachable code)
- `paragonie/random_compat`, `symfony/polyfill-php70`, `symfony/polyfill-php72` (safe to drop at PHP 8.1+)

---

## License

GPLv2 or later — see [LICENSE](LICENSE).
