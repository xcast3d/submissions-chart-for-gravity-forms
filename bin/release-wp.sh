#!/bin/sh
# Prepares a release in a WordPress.org SVN checkout. Only usable after approval.
#
#   svn checkout https://plugins.svn.wordpress.org/submission-chart-add-on-for-gravity-forms ./submission-chart-add-on-for-gravity-forms-svn
#   ./bin/release-wp.sh [/path/to/svn/checkout]
#
# It always shows the complete SVN status and asks before committing.
set -eu
cd "$(dirname "$0")/.."

SLUG="submission-chart-add-on-for-gravity-forms"
SVN_DIR="${1:-./$SLUG-svn}"
SVN_USER="xcastaneda"
ASSET_DIR=".wordpress-org"

if [ ! -d "$SVN_DIR/.svn" ]; then
  echo "No SVN working copy at $SVN_DIR" >&2
  exit 1
fi

VERSION=$(sed -n 's/^ \* Version:[[:space:]]*//p' "$SLUG.php" | head -n 1)
STABLE_TAG=$(sed -n 's/^Stable tag:[[:space:]]*//p' readme.txt | head -n 1)
CONSTANT=$(sed -n "s/^define( 'GFSC_VERSION', '\(.*\)' );/\1/p" "$SLUG.php")

if [ "$STABLE_TAG" != "$VERSION" ] || [ "$CONSTANT" != "$VERSION" ]; then
  echo "Version mismatch: header $VERSION, GFSC_VERSION $CONSTANT, Stable tag $STABLE_TAG" >&2
  exit 1
fi

if [ -d "$SVN_DIR/tags/$VERSION" ]; then
  echo "tags/$VERSION already exists in SVN. Bump the version before releasing." >&2
  exit 1
fi

echo "Preparing $SLUG $VERSION..."
./bin/build-zip.sh

STAGE=$(mktemp -d)
trap 'rm -rf "$STAGE"' EXIT HUP INT TERM
unzip -q "dist/$SLUG-$VERSION.zip" -d "$STAGE"

# Mirror the exact installable package, so removed files don't linger in trunk.
rsync -a --delete --exclude='.svn' "$STAGE/$SLUG/" "$SVN_DIR/trunk/"

# Listing art lives beside trunk and tags, never inside the plugin.
mkdir -p "$SVN_DIR/assets"
rsync -a --delete --exclude='.svn' --exclude='.DS_Store' "$ASSET_DIR/" "$SVN_DIR/assets/"

cd "$SVN_DIR"

svn status | awk '$1 == "!" { print substr($0, 9) }' | while IFS= read -r path; do
  [ -n "$path" ] && svn rm "$path"
done
svn add --force trunk assets -q
find assets -maxdepth 1 -type f -name '*.png' -exec svn propset svn:mime-type image/png {} + >/dev/null
svn cp trunk "tags/$VERSION"

echo
svn status
echo
printf 'Commit %s %s to WordPress.org SVN? [y/N] ' "$SLUG" "$VERSION"
read -r confirm
case "$confirm" in
  y|Y)
    # Compressed commit responses can surface as a bogus invalid UTF-8 error on WordPress.org.
    svn commit -m "Release $VERSION" --username "$SVN_USER" --config-option servers:global:http-compression=no
    ;;
  *)
    echo "Not committed. The working copy at $SVN_DIR remains staged for review."
    ;;
esac
