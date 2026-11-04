# Submission Chart Add-on for Gravity Forms

Adds a **Forms → Submissions Chart** page to the Gravity Forms admin menu with a bar chart of entries per day.

- Form filter with a checkbox per form (**All forms** by default), preselect one form via `admin.php?page=gfsc_submissions_chart&form_id=3`
- Ranges: Last 7 / 30 / 90 days, All Time, Custom
- Timezone-aware (buckets by the site timezone; GF stores entries in UTC)
- Zero-filled days so the x-axis stays continuous
- Bar colour follows the WP admin colour scheme
- **Download CSV** of the current view, with a column per form plus a total, and **Print / PDF** for a one-page report with the date range, chart and per-form totals
- Chart types: Bars, Line, Lines by form (one line per form plus a Total line) and Stacked bars by form. Line and by-form charts have a legend with each series' total for the range. The choice is remembered per browser. Up to 8 forms get their own color; any others fold into "Other forms"
- Click a day: with one form selected it opens GF's Entries list filtered to that date; with several it opens a panel listing that day's entries across those forms, with per-form "Open in Entries" links
- **Recent Submissions**: a 2-column grid with one card per form showing its last 5 entries. Each entry is labelled by the form's first Name field, else the first field labelled "name", else the first field that stores a value. Hovering an entry shows Spam and Trash links with an Undo, following the same visibility rules as GF's Entries list
- Laid out like Gravity Forms' own admin pages (logo header, centered content)
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
