#!/usr/bin/env bash
# Builds dist/submissions-chart-for-gf-<version>.zip from the committed HEAD.
set -euo pipefail
cd "$(dirname "$0")/.."
VERSION=$(sed -n 's/^ \* Version: *//p' submissions-chart-for-gf.php)
mkdir -p dist
git archive --format=zip --prefix=submissions-chart-for-gf/ -o "dist/submissions-chart-for-gf-${VERSION}.zip" HEAD
echo "dist/submissions-chart-for-gf-${VERSION}.zip"
