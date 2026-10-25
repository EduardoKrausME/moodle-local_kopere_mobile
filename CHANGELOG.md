# Changelog

This changelog covers only the two latest published releases of the main `local_kopere_mobile` plugin, verified on October 24, 2026. Each entry is based on its published Git tag compared with the preceding release tag. The October 19, 2026 `Dependencies Kopere` commit occurred after release `1.4.5` and is deliberately excluded.

## [1.4.5] - 2026-10-15

### Fixed
- Fixed the mobile home-summary external web service for clients authenticated with a Moodle web-service token but without a browser session. `classes/external/home_summary.php` no longer calls `require_login()` directly; authentication is handled by the web-service layer, while the method continues to validate the system context.
- Explicitly marked the home-summary service as requiring an authenticated user by adding `loginrequired => true` to its definition in `db/services.php`.

### Tests
- Added `tests/home_summary_test.php` with regression coverage for an authenticated user without enrolled courses and an enrolled user, including checks against the external API's declared return structure.

### CI maintenance
- Updated the PostgreSQL CI service from version 16 to 17.
- Updated the MariaDB CI service from version 10 to 11.4, including its environment setup and health check.

**Full comparison:** [1.4.4...1.4.5](https://github.com/EduardoKrausME/moodle-local_kopere_mobile/compare/1.4.4...1.4.5)

## [1.4.4] - 2026-10-12

### Documentation and assets
- Reworked the README to describe the plugin's Moodle mobile integration, supported offline-aware resources, file delivery and connectivity limitations more precisely.
- Added `pix/icon.png` to the plugin assets.
- Standardized Moodle GPL license/file headers across JavaScript sources, SCORM/PDF-related JavaScript assets, and Mustache templates. These were documentation/header additions rather than changes to the underlying scripts' behavior.
- Corrected Mustache template header references in `templates/config_test.mustache` and `templates/lgpd.mustache`.

### CI and release security
- Updated the CI Moodle test matrix from Moodle 5.2 to Moodle 5.3 for the PHP 8.3 job.
- Added an explicit checkout of the Kopere Dashboard repository and passed it to `moodle-plugin-ci` as an additional plugin, so the CI environment includes this dependency.
- Changed the automatic-release workflow to read the commit message from the `RELEASE_MESSAGE` environment variable rather than interpolating it directly into a shell script, reducing command-injection risk when parsing a release-triggering commit.

**Full comparison:** [1.4.3...1.4.4](https://github.com/EduardoKrausME/moodle-local_kopere_mobile/compare/1.4.3...1.4.4)
