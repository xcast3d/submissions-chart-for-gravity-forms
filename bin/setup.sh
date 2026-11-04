#!/usr/bin/env bash
# First-time setup of the local test site.
#
#   ./bin/setup.sh [path/to/gravityforms.zip]
#
# Gravity Forms is a licensed plugin, so it is never committed. Pass its zip
# (defaults to the newest ~/Downloads/gravityforms_*.zip). It's installed into the
# site's Docker volume, not a bind mount, so WordPress can update it normally.
set -euo pipefail
cd "$(dirname "$0")/.."

WP_PORT="${WP_PORT:-8090}"
WP_URL="http://localhost:${WP_PORT}"
ADMIN_USER="${WP_ADMIN_USER:-admin}"
ADMIN_PASS="${WP_ADMIN_PASS:-admin}"
ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@example.test}"

GF_ZIP="${1:-$(ls -t "$HOME"/Downloads/gravityforms_*.zip 2>/dev/null | head -1 || true)}"
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

if ! wp plugin is-installed gravityforms >/dev/null 2>&1; then
	if [ -z "$GF_ZIP" ] || [ ! -f "$GF_ZIP" ]; then
		echo "Gravity Forms zip not found. Usage: ./bin/setup.sh path/to/gravityforms.zip" >&2
		exit 1
	fi
	echo "→ Installing $(basename "$GF_ZIP")"
	docker compose cp "$GF_ZIP" wordpress:/var/www/html/wp-content/gravityforms-install.zip
	wp plugin install wp-content/gravityforms-install.zip
	docker compose exec -T wordpress rm -f wp-content/gravityforms-install.zip
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
