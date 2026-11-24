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
