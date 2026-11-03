# Submission Chart Add-on for Gravity Forms

Adds a **Forms → Submissions Chart** page to the Gravity Forms admin menu with a bar chart of entries per day.

- Form picker (including **All forms**), deep-linkable via `admin.php?page=gfsc_submissions_chart&form_id=3`
- Ranges: Last 7 / 30 / 90 days, All Time, Custom
- Timezone-aware (buckets by the site timezone; GF stores entries in UTC)
- Zero-filled days so the x-axis stays continuous
- Bar colour follows the WP admin colour scheme
- Chart.js 4.4.3 bundled locally (no CDN)

Assets load only on the plugin's own page, so the Forms list and form editor are untouched.

## Requirements

WordPress 5.8+, PHP 7.4+, Gravity Forms 2.5+.

## Local development

Requires Docker Desktop.

```bash
./bin/setup.sh ~/Downloads/gravityforms_2.8.4.zip
```

This extracts Gravity Forms into the git-ignored `.docker/plugins/`, starts WordPress + MariaDB, installs WordPress, activates both plugins and seeds four sample forms with ~120 days of back-dated entries.

| | |
|---|---|
| Site | http://localhost:8090 (override with `WP_PORT=…`) |
| Chart page | http://localhost:8090/wp-admin/admin.php?page=gfsc_submissions_chart |
| Login | `admin` / `admin` (override with `WP_ADMIN_USER` / `WP_ADMIN_PASS`) |

The repo is bind-mounted as `wp-content/plugins/submissions-chart-for-gf`, so edits are live.

```bash
./bin/wp.sh plugin list                     # any WP-CLI command
docker compose logs -f wordpress            # PHP/Apache output
docker compose exec wordpress tail -f wp-content/debug.log
docker compose down                         # stop (keeps data)
docker compose down -v                      # wipe the site and database
```

Re-seed more entries: `./bin/wp.sh eval-file wp-content/plugins/submissions-chart-for-gf/bin/seed.php 365`

## Release zip

```bash
./bin/build-zip.sh
```

Builds `dist/submissions-chart-for-gf-<version>.zip` from committed `HEAD`, excluding dev files (see `.gitattributes`).

## Structure

```
submissions-chart-for-gf.php      Plugin header + bootstrap (on gform_loaded)
includes/class-gfsc-admin.php     Menu (gform_addon_navigation), assets, AJAX
includes/class-gfsc-data.php      Entry aggregation by local day
includes/views/page.php           Page markup
assets/js/admin.js                Chart + controls
assets/css/admin.css              Styles
assets/vendor/chartjs/            Chart.js 4.4.3 (MIT)
bin/                              Docker setup, WP-CLI wrapper, seeder, zip build
```
