# WineLabel EU

EU Digital Wine Label plugin for WordPress. Generates regulation-compliant digital labels (Reg. EU 2021/2117 — Art. 119) with ingredients, nutritional values, and waste sorting information.

## Features

- **Bare HTML output** — No theme dependency, no cookies, no scripts. Fully compliant with EU regulations.
- **Multi-vintage support** — Each wine can have multiple vintages, each with its own label data.
- **Bilingual labels** (Pro) — English primary + configurable second language (IT, DE, FR, ES, etc.)
- **QR code PDF** (Pro) — Downloadable vector QR codes linking to each label.
- **Index page** (Pro) — `/elabel/` listing all published labels.
- **Works with or without WooCommerce** — Attaches to WooCommerce products when available, or uses a built-in Wines CPT.

## Free vs Pro

| Feature | Free | Pro |
|---|---|---|
| Published vintages | 5 max | Unlimited |
| Ingredients, nutrition, recycling | Yes | Yes |
| Bare HTML regulatory output | Yes | Yes |
| URL routing | Yes | Yes |
| Bilingual labels | EN only | EN + second language |
| QR code PDF download | No | Yes |
| Index page (`/winelabel/`) | Yes (branded) | Yes (customizable) |
| Duplicate vintage action | No | Yes |
| Custom base URL for QR codes | No | Yes |
| White-label (remove footer credit) | No | Yes |

## Requirements

- WordPress 6.0+
- PHP 8.1+
- WooCommerce (optional)

## Installation

1. Download or clone this repository.
2. Run `composer install` in the plugin directory.
3. Upload `winelabel-eu/` to `wp-content/plugins/`.
4. Activate from the WordPress admin.

## URL Structure

| URL | Description |
|---|---|
| `/{slug}-winelabel-{YY}/` | Vintage-specific label |
| `/{slug}-winelabel/` | 301 redirect to latest vintage |
| `/winelabel/` | Index page (slug customizable in Pro) |
| `/{slug}-winelabel-{YY}/?qr=pdf` | QR code PDF download (Pro) |
| `?lang=xx` | Second language toggle (Pro) |

## Development

```bash
# Sync to local DevKinsta site
rsync -a --delete --exclude='.git' ~/winelabel-eu/ ~/DevKinsta/public/<site>/wp-content/plugins/winelabel-eu/

# Regenerate translation template
wp i18n make-pot . languages/winelabel-eu.pot --slug=winelabel-eu --domain=winelabel-eu
```

## License

Proprietary. All rights reserved.
