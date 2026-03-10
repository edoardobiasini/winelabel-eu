#!/usr/bin/env bash
#
# Deploy the landing site to Cloudflare Pages.
#
# Prerequisites:
#   npm install -g wrangler
#   wrangler login
#
# First-time setup:
#   wrangler pages project create winelabel-eu
#
# Usage: ./deploy.sh [production|preview]

set -euo pipefail

ENVIRONMENT="${1:-production}"
LANDING_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "Deploying landing site to Cloudflare Pages ($ENVIRONMENT)..."

if [ "$ENVIRONMENT" = "production" ]; then
	npx wrangler pages deploy "$LANDING_DIR" \
		--project-name=winelabel-eu \
		--branch=main
else
	npx wrangler pages deploy "$LANDING_DIR" \
		--project-name=winelabel-eu \
		--branch=preview
fi

echo "Done."
