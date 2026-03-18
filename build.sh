#!/usr/bin/env bash
#
# Build a distributable ZIP for WineLabel EU.
#
# Usage: ./build.sh
# Output: winelabel-eu-<version>.zip in the parent directory.

set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_SLUG="winelabel-eu"

# Extract version from plugin header.
VERSION=$(grep -m1 "Version:" "$PLUGIN_DIR/$PLUGIN_SLUG.php" | sed 's/.*Version:\s*//' | tr -d '[:space:]')

if [ -z "$VERSION" ]; then
	echo "Error: could not extract version from $PLUGIN_SLUG.php"
	exit 1
fi

ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"
BUILD_DIR=$(mktemp -d)
DEST="$BUILD_DIR/$PLUGIN_SLUG"

echo "Building $ZIP_NAME..."

# Copy plugin files (exclude dev/build artifacts).
rsync -a \
	--exclude='.claude' \
	--exclude='.wrangler' \
	--exclude='.git' \
	--exclude='.gitignore' \
	--exclude='build.sh' \
	--exclude='README.md' \
	--exclude='.DS_Store' \
	--exclude='Thumbs.db' \
	--exclude='.idea/' \
	--exclude='.vscode/' \
	--exclude='*.swp' \
	--exclude='*.swo' \
	--exclude='node_modules/' \
	--exclude='tests/' \
	--exclude='landing/' \
	--exclude='.github/' \
	--exclude='GO-TO-MARKET.md' \
	--exclude='build-lite.sh' \
	--exclude='composer-lite.json' \
	"$PLUGIN_DIR/" "$DEST/"

# Install production Composer dependencies (no dev).
cd "$DEST"
composer install --no-dev --optimize-autoloader --no-interaction --quiet 2>/dev/null || true
rm -f composer.json composer.lock

# Create ZIP.
cd "$BUILD_DIR"
zip -rq "$PLUGIN_DIR/../$ZIP_NAME" "$PLUGIN_SLUG"

# Clean up.
rm -rf "$BUILD_DIR"

echo "Done: ../$ZIP_NAME ($(du -h "$PLUGIN_DIR/../$ZIP_NAME" | cut -f1))"
