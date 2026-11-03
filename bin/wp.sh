#!/usr/bin/env bash
# Run WP-CLI against the Docker site, e.g. ./bin/wp.sh plugin list
set -euo pipefail
cd "$(dirname "$0")/.."
exec docker compose run --rm -T cli wp "$@"
