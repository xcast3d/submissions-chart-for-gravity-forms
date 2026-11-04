=== Submission Chart Add-on for Gravity Forms ===
Contributors: xcast3d
Tags: gravity forms, chart, entries, submissions, reports
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.0.0-dev
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a Forms → Submissions Chart page that graphs Gravity Forms entries per day.

== Description ==

A bar chart of daily submissions for any form, or all forms combined, with 7/30/90-day, all-time and custom date ranges. Counts follow your site's timezone.

Requires Gravity Forms.

== Installation ==

1. Upload the `submissions-chart-for-gf` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Go to Forms → Submissions Chart.

== Changelog ==

= 2.0.0 =
* Chart moved to its own Forms → Submissions Chart page (no longer injected into the Forms list or form editor).
* Added "All forms" and Last 90 Days options.
* Click a day in the chart to see that day's entries.
* Recent Submissions cards: the last 5 entries for each form, with Spam and Trash links.
* Chart types: bars, line, lines by form with a total line, and stacked bars by form.
* Page layout matches Gravity Forms' own admin screens.
* Chart.js bundled locally instead of loaded from a CDN.
* Faster aggregation for forms with many entries.

== Third-party libraries ==

Chart.js 4.4.3 — MIT License — https://www.chartjs.org/
