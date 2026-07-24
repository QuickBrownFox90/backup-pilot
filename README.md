# BackupPilot — WordPress Backup Plugin

Schedule complete automatic backups of your WordPress installation and push them to an external storage service.

**BackupPilot is a fork of the excellent [BackWPup](https://wordpress.org/plugins/backwpup/) plugin (GPLv2+) by Inpsyde, rebranded and maintained independently.**

> **Use in production at your own risk.**
> BackupPilot has not yet been reviewed or tested by an independent community. It is maintained by a single developer. Treat it as you would any pre-release software: test in a staging environment first, keep an independent copy of your backups, and verify restores before relying on them in production.

---

## About

BackupPilot is aimed at experienced WordPress administrators who want a reliable, no-nonsense backup tool — without upsells, cloud-account lockout, or UI bloat that comes from a freemium model.

The fork base is BackWPup **3.10.0** — a deliberate choice. BackWPup was an outstanding plugin for years, but went through a series of ownership changes that gradually stripped out the free version, added friction to basic features, and steered users toward paid tiers. Version 3.x represented the last point where the plugin was still clean, complete, and community-oriented.

BackupPilot picks up from there: updated dependencies, security fixes, and an opinionated focus on the features that actually matter for a professional backup workflow.

This is a side project maintained by one person in their spare time — not a product with a roadmap, a support team, or a commitment to feature requests. If this fork doesn't fit your workflow exactly, the codebase is intentionally kept clean and forkable. GPLv2 applies; take it and run.

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
- Send backups to an email address *(not recommended, but maybe useful for some edge cases)*
- Send logs by email
- Multisite support (network admin only)

## Requirements

- **WordPress** 6.0 or later
- **PHP** 8.1 or later
- For full functionality: PHP extensions `mysqli`, `ftp`, `gz`, `bz2`, `ZipArchive`, `curl`

Features that cannot run due to your server configuration will not appear in the admin area.

## Installation

1. Download the latest `backup-pilot.zip` from the [Releases page](https://github.com/QuickBrownFox90/backup-pilot/releases).
2. In your WordPress admin go to **Plugins → Add New → Upload Plugin**, select the ZIP and click **Install Now**.
3. Activate the plugin, then go to **BackupPilot → Settings** to configure your first backup job.

Once installed, future updates appear in the standard WordPress update flow — no manual download needed.

---

## Frequently Asked Questions

### How do I restore a backup?

BackupPilot does not include an automated restore function — restores are done manually. Extract the downloaded archive, upload its contents back to your server, then import the included database dump with a tool like phpMyAdmin or WP-CLI (`wp db import dump.sql`).

For a restore to work, the archive needs to contain a complete WordPress installation — both the files and the database dump.

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

### 1.0.6

- Fixed: Plugin header `Version` stayed at `1.0.4` after the 1.0.5 release — the 1.0.5 release commit updated the readme/changelog but not the `Version:` header in `backuppilot.php`, so the running plugin still reported itself as `1.0.4` (visible in the Plugins list, `WP_DEBUG`, and the self-hosted GitHub updater's version comparison) despite `1.0.5` already being installed; header bumped to `1.0.6` to resolve the mismatch

### 1.0.5

- Added: "Check for updates" link in the plugin row on the Plugins page — clears the 24 h GitHub release cache and triggers a fresh update check without needing WP-CLI or direct database access
- Fixed: Clicking "Abort" on a running job rendered a blank page — added a redirect back to the Jobs overview after the abort is triggered
- Fixed: `get_upload_dir()` on multisite used hardcoded path probing (`is_dir()`) instead of the WordPress API — custom upload paths set via the `UPLOADS` constant or `upload_path` option were silently ignored; now uses `switch_to_blog( get_main_site_id() )` + `wp_upload_dir()` to resolve the network-level base directory correctly
- Fixed: Normal-mode log only showed the 6-line header and nothing else on production hosts where `error_reporting` is `0` (a common `php.ini` default on shared hosting, or set by a plugin) — `log()` returns early for `error_reporting()==0` to filter `@`-suppressed PHP errors when acting as an error handler, but that same check silently dropped every direct `log()` call; Debug mode worked because it already called `error_reporting(-1)` in `run()`, Normal mode did not; `run()` now restores a comprehensive reporting level (`E_ALL` without `DEPRECATED`) when it finds `0`
- Fixed: FTP SSL close_notify warning (`ftp_close(): SSL_read on shutdown …`) still appeared in the backup log despite `@ftp_close()` — PHP 8.0+ changed `@` operator semantics: inside a `set_error_handler` callback, `error_reporting()` no longer returns `0` for `@`-suppressed errors but a reduced fatal-only mask that excludes `E_WARNING`; `log()`'s suppression check was updated from `== 0` to `!(error_reporting() & $type)` to work correctly on both PHP 7 and PHP 8
- Fixed: BackupPilot's generated manifest was silently overwritten inside the archive by a `manifest.json` present in the WordPress root (e.g. a PWA web app manifest) — extra files are written to the archive before the regular file scan runs, so the WP-root copy added later wins on extraction; renamed the generated file to `backuppilot_manifest.json` to eliminate the collision
- Added: Optional "Notes" column in the jobs overview — enable via Screen Options ("Ansicht anpassen"); each job can carry a free-text notes field (editable on the General tab) for documenting purpose, destination context, or anything else; long notes are truncated in the column with the full text visible on hover
- Fixed: Migrating from BackWPup left FTP (and other) passwords unusable — BackWPup stores passwords encrypted with the prefix `$BackWPup$`, which `BackupPilot_Encryption::decrypt()` did not recognise, causing it to return the raw ciphertext as the password value; the migration now rewrites the prefix to `$BackupPilot$` before storage (key material is identical: `DB_NAME . DB_USER . DB_PASSWORD` on the same WordPress installation)
- Fixed: Migrating from BackWPup left path-based settings (log folder, local backup destination, etc.) pointing at non-existent directories — BackWPup path segments such as `backwpup-abc123-logs/` are now rewritten to `backuppilot-abc123-logs/` during migration; the hash is preserved because `backwpup_cfg_hash` is migrated to `backuppilot_cfg_hash` before any path is evaluated
- Fixed: Job name missing from the "Job currently running" heading on the jobs page — `esc_html()` was called without `echo`, so the return value was silently discarded and the `<h2>` rendered empty
- Fixed: CSS rule for the running-job heading had a typo in the selector (`h2#runnigtitle` instead of `h2#runningtitle`) and never matched the element

### 1.0.4

- Fixed: Fatal error when BackupPilot is activated while BackWPup is still active — `remove_invalid_characters_from_directory_name()` name collision; function is now guarded with `function_exists()`, and the activation hook aborts with a clear error message if BackWPup is detected
- Removed: Dead upgrade paths for BackWPup 2.x and 3.0.x — no BackupPilot installation can have data that old
- Fixed: FTP destination silently reported success when the TLS data channel failed — `ftp_nb_fput()` returns `FTP_FINISHED` immediately on SSL handshake failure, leaving a 0-byte file on the server; a post-transfer `ftp_size()` check now catches this and logs a clear error with a hint about TLS session resumption
- Fixed: FTP destination logged a spurious warning when the server closed the TLS connection without a proper `close_notify` alert; suppressed via `@ftp_close()` after the transfer has already been verified

### 1.0.3

- Removed: About page and all associated marketing assets — the page was already disabled and served no purpose beyond BackWPup promotional content
- Removed: Custom icon font (`backuppilot.eot/ttf/woff/svg`) — replaced with WordPress Dashicon `dashicons-backup` in the admin menu and admin bar, eliminating a dedicated font load for a single glyph
- Removed: 9 unused asset files — dead images and vendor JS duplicates left over from the initial rebrand
- Fixed: Plugin activation redirect pointed to the now-removed About page; now redirects to the Jobs overview
- Added: Self-hosted plugin update system via GitHub Releases (`inc/class-updater.php`) — WordPress's standard update flow (dashboard badge, "Update now") now works without wordpress.org; the updater queries the GitHub API for the latest release, caches the result for 24 h, and prefers an explicitly attached ZIP asset (built by a new GitHub Actions workflow with the correct `backup-pilot/` root folder) over the auto-generated zipball

### 1.0.2

**Translations**
- Removed: `de_DE_formal` — the formal German translation doesn't fit the tone of a plugin built for people who know their way around WordPress.

**Dependencies**
- Removed: `swiftmailer/swiftmailer` — SwiftMailer was officially abandoned in November 2021 and receives no further security patches. Email delivery is now handled entirely by WordPress's native `wp_mail()` function, delegating transport to whatever mail configuration the site already has in place (WP Mail SMTP, FluentSMTP, Post SMTP, or WordPress's built-in PHP mail). The per-job "Sending method" configuration (SMTP host/port/credentials, Sendmail path) has been removed from the Email destination settings accordingly, giving site owners a single place to manage all outgoing mail rather than duplicating transport settings inside BackupPilot.

**Email destination improvements**
- Archive readability is verified before sending; a clear error with the full file path is logged if the file cannot be found
- PHP memory headroom is requested upfront before attaching large archives, matching the safety margin the former SwiftMailer code applied
- The file-size limit error now includes the actual archive size and the configured limit
- Mail send failures are captured via `wp_mail_failed` and logged with the PHPMailer error detail, making MTA-level rejections (e.g. Postfix message size limit) diagnosable from the job log

**Code quality**
- Started cleaning up the inherited code style across `inc/`: tabs converted to 4-space indentation, missing semicolons before `?>` in inline PHP added, `array()` converted to short syntax `[]` throughout, and `} else {` / `} elseif` blocks split onto separate lines — the original codebase was a collage of several different coding styles and this is an ongoing effort

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
