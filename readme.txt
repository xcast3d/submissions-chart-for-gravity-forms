=== Submission Chart Add-on for Gravity Forms ===
Contributors: xcastaneda
Tags: gravity forms, chart, submissions, entries, reports
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

See Gravity Forms submissions at a glance: a chart by day and form, recent entries for each form, CSV download and a printable report.

== Description ==

**Independent add-on:** Submission Chart Add-on for Gravity Forms is made and supported by Xavier Castaneda. It is not an official Gravity Forms add-on and isn't affiliated with or endorsed by Rocketgenius, the makers of Gravity Forms. It requires Gravity Forms, a separate commercial plugin, to be installed and active. Support: https://github.com/xcast3d/submissions-chart-for-gravity-forms/issues

Gravity Forms shows you entries one form at a time. This add-on adds a **Forms → Submissions Chart** page that shows how all your forms are doing together.

= At a glance =

* **Chart your submissions by day:** bars, a line, one line per form with a total line, or stacked bars by form.
* **Pick your forms and dates:** tick any set of forms, then choose the last 7, 30 or 90 days, all time, or a custom range.
* **Click a day to see its entries:** one form opens the Gravity Forms Entries list filtered to that date. Several forms open a list of that day's entries across them.
* **See recent submissions:** a module for each form shows its latest 5 entries, with Spam and Trash links and an Undo.
* **Report on it:** download what's on screen as a CSV, or print it as a one-page report and save it as a PDF.

= Good to know =

* Counts follow your site's timezone setting.
* The plugin only reads and displays your existing Gravity Forms data. It doesn't add database tables or settings.
* Nothing is sent to external services. Chart.js is included in the plugin rather than loaded from a CDN.
* Spam and Trash follow the same permissions as the Gravity Forms Entries list.

== Support ==

Use this plugin's WordPress.org support forum, or open an issue at https://github.com/xcast3d/submissions-chart-for-gravity-forms/issues with your WordPress and Gravity Forms versions.

== Installation ==

1. Install and activate **Gravity Forms** 2.5 or later.
2. Install and activate **Submission Chart Add-on for Gravity Forms**.
3. Go to **Forms → Submissions Chart**.

== Frequently Asked Questions ==

= Does this work without Gravity Forms? =

No. Gravity Forms is a separate commercial plugin and must be installed and active. Without it, this add-on shows a notice and does nothing else.

= Is this an official Gravity Forms add-on? =

No. It's an independent add-on, and Gravity Forms' support team doesn't support it. Please use this plugin's support forum.

= Who can see the page? =

Anyone Gravity Forms allows to view entries. The Spam link also requires permission to edit entries, and the Trash link requires permission to delete them.

= Why doesn't my chart count spam or trashed entries? =

The chart only counts active entries, and leaves out entries belonging to trashed forms. Moving an entry to spam or the trash updates the chart right away, and Undo restores it.

= How do I save a report as a PDF? =

Click **Print / PDF**, then choose "Save as PDF" in your browser's print dialog. The report has the date range, chart and per-form totals. It leaves out entry details such as names.

= What does the CSV contain? =

The daily counts currently on screen: a date column, a column for each form, and a total. It contains counts only, not entry details.

= Why do some of my forms show as "Other forms"? =

The chart has 8 distinct colors. If more than 8 forms are shown, the busiest forms get their own color and the rest are combined, so the chart stays readable. Tick fewer forms to see any of them separately.

= Does it delete anything when uninstalled? =

The plugin doesn't store any data of its own, so there's nothing to clean up. Your forms and entries are untouched.

== Screenshots ==

1. The Submissions Chart page with one line per form, a total line and a legend.
2. The form filter, chart types and date ranges.
3. Recent submissions for each form, with Spam and Trash links.
4. The printable report, ready to save as a PDF.

== Upgrade Notice ==

= 1.0.0 =
First public release. Requires Gravity Forms 2.5 or later.

== Changelog ==

= 1.0.0 =
* First public release.
* Forms → Submissions Chart page with bar, line, lines by form and stacked bar charts.
* Form filter, date ranges and click-a-day entry lists.
* Recent submissions for each form, with Spam and Trash links and Undo.
* CSV download and a printable report.

== Third-party libraries ==

This plugin includes the minified build of Chart.js 4.4.3, which is released under the MIT License (`assets/vendor/chartjs/LICENSE.md`). Human-readable source: https://github.com/chartjs/Chart.js/tree/v4.4.3
