#!/usr/bin/env bash
# First-time setup of the local test site.
#
#   ./bin/setup.sh [path/to/gravityforms.zip]
#
# Gravity Forms is a licensed plugin, so it is never committed. Pass its zip
# (defaults to the newest ~/Downloads/gravityforms_*.zip); it is extracted into
# .docker/plugins/ which is git-ignored and mounted into the container.
set -euo pipefail
cd "$(dirname "$0")/.."

WP_PORT="${WP_PORT:-8090}"
WP_URL="http://localhost:${WP_PORT}"
ADMIN_USER="${WP_ADMIN_USER:-admin}"
ADMIN_PASS="${WP_ADMIN_PASS:-admin}"
ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@example.test}"

GF_ZIP="${1:-$(ls -t "$HOME"/Downloads/gravityforms_*.zip 2>/dev/null | head -1 || true)}"
if [ ! -f .docker/plugins/gravityforms/gravityforms.php ]; then
	if [ -z "$GF_ZIP" ] || [ ! -f "$GF_ZIP" ]; then
		echo "Gravity Forms zip not found. Usage: ./bin/setup.sh path/to/gravityforms.zip" >&2
		exit 1
	fi
	echo "→ Extracting $(basename "$GF_ZIP")"
	mkdir -p .docker/plugins
	unzip -q -o "$GF_ZIP" -d .docker/plugins
fi

echo "→ Starting containers"
docker compose up -d --wait

wp() { docker compose run --rm -T cli wp "$@"; }

echo "→ Waiting for WordPress files"
until wp core version >/dev/null 2>&1; do sleep 2; done

if ! wp core is-installed >/dev/null 2>&1; then
	echo "→ Installing WordPress"
	wp core install --url="$WP_URL" --title="GF Submissions Chart Dev" \
		--admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" \
		--admin_email="$ADMIN_EMAIL" --skip-email
fi

wp plugin activate gravityforms submission-chart-add-on-for-gravity-forms
wp option update gform_pending_installation 0 >/dev/null
wp option update timezone_string "America/New_York" >/dev/null

if [ "$(wp eval 'echo count( GFAPI::get_forms() );')" = "0" ]; then
	echo "→ Seeding sample forms and entries"
	wp eval-file wp-content/plugins/submission-chart-add-on-for-gravity-forms/bin/seed.php
fi

echo
echo "✓ Ready: ${WP_URL}/wp-admin/admin.php?page=gfsc_submissions_chart"
echo "  Login: ${ADMIN_USER} / ${ADMIN_PASS}"
