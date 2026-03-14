=== WineLabel EU ===
Contributors: edoardobiasini
Tags: wine, eu regulation, digital label, qr code, woocommerce
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

EU-regulation-compliant digital wine labels with ingredients, nutritional values, and waste sorting information.

== Description ==

WineLabel EU helps wineries comply with **EU Regulation 2021/2117** (Art. 119 of Reg. EU 1308/2013) by generating digital wine labels accessible via QR code.

Each label includes:

* **Ingredients** — raw materials, acidity regulators, stabilizers, antioxidants, sulfite declaration
* **Nutritional values** — calories, fat, carbohydrates, sugars, protein, salt, alcohol content
* **Waste sorting** — packaging components with material codes and collection instructions

= How it works =

1. Add a wine using the built-in Wines manager (or attach labels to WooCommerce products)
2. Enable the digital label and add one or more vintages
3. Fill in ingredients, nutritional values, and waste sorting for each vintage
4. Share the auto-generated URL or print the QR code

Labels are served as clean, standalone HTML pages — no theme interference, no cookies, no tracking — exactly as EU regulations require.

= WooCommerce Integration =

WineLabel EU works standalone with its own Wines post type. If WooCommerce is installed, you can optionally attach digital labels directly to your existing products. Toggle this in Settings.

= Free vs Pro =

The free version includes everything you need to get started:

* Up to 5 published vintages
* Full ingredient, nutritional, and waste sorting fields
* Clean, regulation-compliant label pages
* Works with or without WooCommerce

[Upgrade to Pro](https://winelabel.net) for:

* Unlimited vintages
* Bilingual labels (English + a second language)
* Downloadable QR code PDFs
* Custom base URL for QR codes
* Label index page
* Remove footer branding
* Priority support

== Installation ==

1. Upload the `winelabel-eu` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to **WineLabel EU → Settings** to configure
4. Add wines via **WineLabel EU → Wines** (or WooCommerce Products if enabled)
5. Create vintages and fill in label data

== Frequently Asked Questions ==

= What EU regulation does this address? =

EU Regulation 2021/2117, which amended Regulation 1308/2013 (Art. 119). Since December 2023, wines sold in the EU must provide digital access to ingredients, nutritional information, and recycling instructions.

= Do I need WooCommerce? =

No. WineLabel EU includes a built-in Wines manager. WooCommerce integration is optional and can be enabled in Settings if you want to attach labels to existing products.

= What does the label page look like? =

Labels are served as clean HTML pages with no theme styles, no cookies, and no JavaScript — just the regulatory information. This ensures compliance and fast loading on any device.

= Can I use this in languages other than English? =

The free version displays labels in English. The Pro version adds bilingual support with a configurable second language (Italian, German, French, Spanish, etc.) and fully customizable label strings.

= Is there a limit on the free version? =

Free users can publish up to 5 vintages across all wines. Upgrade to Pro for unlimited vintages.

= Where can I get support? =

For bug reports and feature requests, visit [our GitHub repository](https://github.com/edoardobiasini/winelabel-eu). For Pro support, contact us at [winelabel.net](https://winelabel.net).

== Screenshots ==

1. Wine editor with digital label fields enabled
2. Vintage editor — ingredients, nutritional values, and waste sorting
3. Frontend digital label page (mobile view)
4. Settings page with WooCommerce integration toggle
5. Usage dashboard showing vintage count

== Changelog ==

= 1.0.4 =
* Support plain permalinks (query-param fallback for all label URLs)
* Auto-flush rewrite rules on plugin activation
* Clear license transient on activation and uninstall
* Settings page reflects correct URL format for current permalink structure

= 1.0.3 =
* Fix license activation failing due to LemonSqueezy API response mismatch
* Add required Accept/Content-Type headers to license API requests
* Add debug logging for license validation when WP_DEBUG is enabled
* Fix license key placeholder to match LemonSqueezy key format

= 1.0.2 =
* Fixed fatal error on lite version (PHP function hoisting)
* GPL-2.0-or-later license for WordPress.org compatibility
* Added readme.txt for WordPress.org plugin directory
* Improved security: proper escaping, wp_unslash, wp_safe_redirect throughout
* Tested up to WordPress 6.9

= 1.0.1 =
* Added WooCommerce integration as opt-in toggle (standalone Wines manager by default)
* Improved lite version compatibility (PHP 7.4+)
* Fixed Carbon Fields container naming

= 1.0.0 =
* Initial release
* Ingredients, nutritional values, and waste sorting fields
* Multi-vintage support with per-vintage data
* Clean standalone HTML label pages
* WooCommerce integration (optional)
* Free tier with 5-vintage limit

== Upgrade Notice ==

= 1.0.1 =
WooCommerce integration is now opt-in. If you were using WooCommerce, go to WineLabel EU → Settings to re-enable it.
