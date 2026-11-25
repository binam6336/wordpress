# mytheme 0.8.0

## Changes in 0.8.0

- Added four Elementor demos: an editorial news site, bags and shoes, contemporary clothing, and a supermarket. Velour remains the cosmetics demo; Home Decor has a deliberate upgrade path to a new luxury watch presentation. Six demos are offered in total.
- New demo pages use independent palettes and layouts, with local sample photographs. Store products and news posts are marked as theme samples. Switching demos moves only the previous demo's marked samples to Trash and restores them on return.
- The existing Home Decor installation can be upgraded from its demo card. Its Elementor page data is backed up first; legacy sample products are moved to Trash after the new import finishes. Merchant content is not rewritten.
- Added theme settings for a WordPress media-library logo and site icon, with image-size guidance, and a reorderable header menu with page, category and custom-link destinations.
- Refreshed the demo selection screenshots. See `demos/expanded/IMAGE-SOURCES.md` for sample photograph sources.

## Verification for 0.8.0

Imported the four new demos and upgraded the existing watch demo in an isolated WordPress staging site. Checked homepage rendering and sample counts, news-post Trash behavior after switching, logo and site icon save/render, custom menu save/reorder/render, and responsive screenshots. Checked all six demo home/shop/contact/cart/checkout/detail routes at 390 and 1440 pixels, and all homepages at 820 pixels: HTTP 200, no horizontal overflow, broken images or browser exceptions. Retried an interrupted watch-demo upgrade from its saved progress and completed it; its Elementor backup and new product set were verified. No real order or payment was created.

---

# mytheme 0.7.0

## Changes in 0.7.0

- Added a top-level theme settings screen with separate appearance, WooCommerce, health, and log sections. The guided setup remains available.
- Bundled 20 locally served OFL Arabic and Persian font families with their licenses and five palettes for each demo.
- Added Elementor product image and product grid widgets that read real WooCommerce products. Editors can choose newest, oldest, lowest or highest price, popularity, and featured products. Existing theme-owned demo homepages are backed up before their generated media widgets are migrated.
- Added a checkout mode switch between the demo shortcode page and a separate official WooCommerce Checkout block page. Existing customized checkout content is preserved.
- Added bounded diagnostic logs split by component and day, plus a read-only integrity screen for core and required plugin checksums and executable files in uploads. This is a triage aid, not a complete malware scanner or a definitive plugin conflict detector.
- Corrected Elementor configuration on canvas cart and checkout pages, and refreshed both demos' selection previews.

## Verification for 0.7.0

In an isolated WordPress staging installation, imported both demos, checked their homepages and dynamic product imagery, tested product sorting, appearance save/reset, checkout mode switching, block rendering with a cart item, and core checksum scan (2,148 files with no mismatch). A WooCommerce checksum scan inspected 4,189 files with no mismatch when the staging-only TLS validation issue was bypassed for that diagnostic run; normal theme requests still require certificate validation. Checked the Home Decor home, shop, product, cart, checkout, and contact pages at 390, 820, and 1440 px: HTTP 200 and no horizontal overflow. PHP and JavaScript syntax checks passed. No real payment or order was created.

## Limits in 0.7.0

- The staging container's certificate chain prevented retrieving the official plugin checksum feed, so plugin checksum results could not be verified there. The UI reports this as an incomplete scan.
- The diagnostic logger starts after theme loading and cannot capture errors before WordPress loads it. It can identify suspicious files or error sources, but cannot conclusively identify malware or prove a plugin conflict.
- Production hosting, payment gateways, and checkout translations were not available to test.

---

# mytheme 0.6.0

## Changes

- Rebuilt both demo presentations: Velour uses a split editorial cover and refined dark product cards; Home Decor uses an asymmetric collection grid and a contemporary light storefront. Both keep their own palettes and Elementor content.
- Unified the store header and footer across Elementor canvas pages, shop archives, and WooCommerce product templates.
- Added per-demo appearance controls for accent, font, header, and footer, with reset and conflict detection.
- Added a dashboard, health screen, live signed demo previews, separate installation and activation, progress reporting, a change review, and rollback.
- On a demo switch, only theme-generated sample products identified by both demo metadata and SKU are moved to Trash. Activating that demo again restores them. Merchant products, orders, and customers are not changed.
- Improved Persian WooCommerce price presentation and the mini cart, including variable product additions.
- Added shop search, category, price-range, and sort controls. Size/material filters remain dependent on real product attributes.

## Verification

Tested in an isolated WordPress/WooCommerce/Elementor Docker installation with both demos imported. Checked 24 demo/page/viewport combinations at 390, 820, and 1440 px: HTTP 200, no horizontal overflow, no browser exceptions, and no failed network responses. Checked simple and variable products in the mini cart, quantity change and removal; demo switch and rollback; Trash/restore of six sample products per demo with an unrelated product preserved; appearance save/reset; invalid preview and AJAX nonce responses. PHP and JavaScript syntax checks passed.

## Known limits

- Sample products have no verified dimensions, materials, ingredients, or installation instructions. The theme does not invent those facts.
- Material and size filters require trusted product attributes to be supplied by the merchant.
- The original Action Scheduler placeholder translation issue was not reproduced and was not attributed to this theme.
- Payment gateway, tax, shipping, real-order, and production-cache behavior were not exercised in staging.
- The “new arrivals” story destination is corrected for new imports; previously customized Elementor page data is not overwritten automatically.
