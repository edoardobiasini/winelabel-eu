#!/usr/bin/env bash
#
# Build a WordPress.org-compatible "lite" ZIP for WineLabel EU.
#
# Differences from full version:
#   - Only Carbon Fields as Composer dependency (no dompdf, php-qrcode, update-checker)
#   - QR PDF gracefully disabled (Pro-only anyway)
#   - Auto-updater skipped (WordPress.org handles updates)
#
# Usage: ./build-lite.sh
# Output: winelabel-eu.zip in the parent directory.

set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_SLUG="winelabel-eu"

# Extract version from plugin header.
VERSION=$(grep -m1 "Version:" "$PLUGIN_DIR/$PLUGIN_SLUG.php" | sed 's/.*Version:\s*//' | tr -d '[:space:]')

if [ -z "$VERSION" ]; then
	echo "Error: could not extract version from $PLUGIN_SLUG.php"
	exit 1
fi

ZIP_NAME="${PLUGIN_SLUG}.zip"
BUILD_DIR=$(mktemp -d)
DEST="$BUILD_DIR/$PLUGIN_SLUG"

echo "Building lite version v${VERSION}: $ZIP_NAME..."

# Copy plugin files (exclude dev/build artifacts, landing, vendor, dot-dirs).
rsync -a \
	--exclude='.claude/' \
	--exclude='.wrangler/' \
	--exclude='.git/' \
	--exclude='.gitignore' \
	--exclude='build.sh' \
	--exclude='build-lite.sh' \
	--exclude='README.md' \
	--exclude='GO-TO-MARKET.md' \
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
	--exclude='vendor/' \
	--exclude='composer.json' \
	--exclude='composer.lock' \
	--exclude='composer-lite.json' \
	"$PLUGIN_DIR/" "$DEST/"

# Use lite composer.json (Carbon Fields only).
cp "$PLUGIN_DIR/composer-lite.json" "$DEST/composer.json"

# Strip auto-updater block (WordPress.org scanner rejects class name strings).
sed -i.bak '/Auto-Updates (GitHub/,/^}/d' "$DEST/$PLUGIN_SLUG.php"
rm -f "$DEST/$PLUGIN_SLUG.php.bak"

# Install lite Composer dependencies (fail loudly if composer is missing).
cd "$DEST"
composer install --no-dev --optimize-autoloader --no-interaction
rm -f composer.lock

# Sanity check: ensure no Pro-only packages leaked in.
for pkg in dompdf chillerlan yahnis-elsts; do
	if [ -d "$DEST/vendor/$pkg" ]; then
		echo "ERROR: Pro-only package '$pkg' found in vendor/. Aborting."
		rm -rf "$BUILD_DIR"
		exit 1
	fi
done

# Create ZIP.
cd "$BUILD_DIR"
zip -rq "$PLUGIN_DIR/../$ZIP_NAME" "$PLUGIN_SLUG"

# Clean up.
rm -rf "$BUILD_DIR"

echo "Done: ../$ZIP_NAME ($(du -h "$PLUGIN_DIR/../$ZIP_NAME" | cut -f1))"
echo ""
echo "This ZIP is suitable for WordPress.org submission."
