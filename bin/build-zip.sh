#!/usr/bin/env bash
# Builds an installable ZIP of the working tree:
#   dist/submission-chart-add-on-for-gravity-forms-<version>.zip
# Files listed in .distignore are left out, the same way a WordPress.org release would.
set -euo pipefail
cd "$(dirname "$0")/.."

SLUG=submission-chart-add-on-for-gravity-forms
VERSION=$(sed -n 's/^ \* Version: *//p' "$SLUG.php")
OUT="dist/$SLUG-$VERSION.zip"

STAGE=$(mktemp -d)
trap 'rm -rf "$STAGE"' EXIT

rsync -a --exclude-from=.distignore ./ "$STAGE/$SLUG/"

mkdir -p dist
rm -f "$OUT"
(cd "$STAGE" && zip -qr - "$SLUG") > "$OUT"
echo "built $OUT"
unzip -l "$OUT" | tail -n +4 | sed '$d' | sed '$d' | awk '{print "  " $4}'
