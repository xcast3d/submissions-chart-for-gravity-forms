# Submitting to WordPress.org

Based on what we learned getting Forms Converter Add-on For HTML Page Publisher approved. Its full notes are in `html-page-publisher-test/docs/`.

- **Name and slug:** Submission Chart Add-on for Gravity Forms / `submission-chart-add-on-for-gravity-forms`. WordPress.org builds the slug from the name, and the text domain, main file and folder must match it.
- **Naming:** review rejected the Forms Converter add-on's first name for starting with a generic term. If this name gets the same response, use a brand-led fallback such as "XCAST3D Submission Chart for Gravity Forms". "for Gravity Forms" must stay at the end because it's someone else's trademark.
- **Account:** `xcastaneda`. The account that submits the plugin owns it. Keep two-factor authentication on.
- **Support:** the WordPress.org forum and GitHub issues. The plugin header and readme link to the GitHub repo, so it must be public before submitting.

## Before uploading

- [x] Make the GitHub repo public, since the Plugin URI and support links point to it
- [x] Version matches in the plugin header, `GFSC_VERSION`, `Stable tag` and the changelog
- [x] `Requires at least` and `Requires PHP` match in the header and readme
- [x] No `Requires Plugins` header: Gravity Forms isn't on WordPress.org, so the plugin shows its own notice instead
- [x] Readme says the plugin is independent, not affiliated with Rocketgenius, and needs a separate commercial plugin
- [x] Bundled minified Chart.js has its license and a link to its source
- [x] No external requests, and everything is prefixed `gfsc` / `GFSC`
- [x] Plugin Check clean on the **release ZIP's contents** (see below): no errors or warnings on 2026-11-04, with low-severity and experimental checks on
- [x] Translation template generated in `languages/`
- [x] Tested with Gravity Forms inactive and too old: correct notice, no fatal error or PHP notices, chart page blocked
- [x] WordPress 7.1 with `WP_DEBUG` on: nothing in `debug.log`
- [ ] Official [Readme Validator](https://wordpress.org/plugins/developers/readme-validator/) clean
- [ ] Install the exact ZIP through Plugins → Add New → Upload Plugin on a fresh database
- [ ] Test the oldest supported WordPress (5.8) and Gravity Forms (2.5). So far it's only been tested on WordPress 7.1 with Gravity Forms 2.8.4
- [ ] Test as a non-administrator with limited Gravity Forms permissions in a real browser session
- [ ] Try the Print / PDF button in Chrome, Safari and Firefox

## Plugin Check

Run it on the ZIP's contents, not this folder. The folder has hidden and dev files that `.distignore` keeps out of the release, which Plugin Check would report as errors.

```bash
./bin/build-zip.sh
./bin/wp.sh plugin install plugin-check --activate
docker compose cp dist/submission-chart-add-on-for-gravity-forms-1.0.0.zip wordpress:/tmp/release.zip
docker compose exec -T -u www-data wordpress sh -c 'cd wp-content/plugins && mkdir gfsc-release-check && cd gfsc-release-check && php -r "\$z=new ZipArchive; \$z->open(\"/tmp/release.zip\"); \$z->extractTo(\".\");" && mv submission-chart-add-on-for-gravity-forms/* . && rmdir submission-chart-add-on-for-gravity-forms'
./bin/wp.sh plugin check gfsc-release-check --slug=submission-chart-add-on-for-gravity-forms --include-low-severity-errors --include-low-severity-warnings --include-experimental
```

Afterwards, delete `wp-content/plugins/gfsc-release-check` from the container.

## Submit

1. Sign in to WordPress.org as `xcastaneda` and make sure mail from `plugins@wordpress.org` isn't going to spam.
2. Upload the ZIP at https://wordpress.org/plugins/developers/add/. It contains the code and readme only. Listing artwork goes to SVN after approval.
3. Confirm the slug before proceeding. Slugs are hard to change after approval.
4. Reply in the same email thread to any review feedback, and upload the fixed ZIP through the same submission. Don't submit a second copy.

## After approval

1. Check out `https://plugins.svn.wordpress.org/submission-chart-add-on-for-gravity-forms/` to `./submission-chart-add-on-for-gravity-forms-svn` (git-ignored).
2. Run `./bin/release-wp.sh`. It mirrors the ZIP into `trunk/`, copies `.wordpress-org/` into the top-level `assets/`, tags the version, shows the status and asks before committing.
3. There's no draft mode after the first SVN push, so only commit when you're ready for the listing to go live.
4. Check the public page, screenshots, compatibility info and the independent-add-on wording once the caches update.

## Listing artwork

`.wordpress-org/` holds the banners, icons and screenshots. Every screenshot needs a matching numbered caption in `readme.txt`. Keep Gravity Forms' logo off the banner and icon, because it's their trademark and the add-on is unofficial. Use test data in screenshots so no client content appears.
