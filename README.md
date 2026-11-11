# Dynamic Filters

Dynamic Filters is a WordPress plugin that adds product filtering to WooCommerce stores.

It helps shoppers narrow a product catalog using the filters configured for the store, while keeping the experience connected to the store's existing products and taxonomy structure.

![WordPress](https://img.shields.io/badge/WordPress-Plugin-21759B?logo=wordpress&logoColor=white)
![WooCommerce](https://img.shields.io/badge/WooCommerce-Extension-96588A?logo=woocommerce&logoColor=white)

## What it does

- Provides product filtering for WooCommerce catalogs.
- Uses the store's configured filter mappings.
- Updates the product results when shoppers apply filters.

**Current behavior:** frontend filtering uses a page reload to display the matching results. AJAX-based frontend filtering is not currently active.

## Requirements

- WordPress
- WooCommerce

Use a development or staging environment to verify compatibility with your WordPress version, WooCommerce version, active theme, and other catalog extensions.

## Installation

1. Download or clone this repository.
2. Copy the plugin directory into `wp-content/plugins/`.
3. Activate **Dynamic Filters** from the WordPress administration panel.
4. Configure the available filters in the plugin's administration screen.
5. Test the filtering experience with your store's product catalog before deploying to production.

## Validation checklist

Before deploying a change, verify:

- [ ] The plugin activates without PHP errors.
- [ ] The administration screen loads and displays the configured mappings.
- [ ] Filters return the expected products for the store's catalog.
- [ ] Empty results and unusual filter combinations are handled correctly.
- [ ] The interface remains usable on mobile and with keyboard navigation.
- [ ] The plugin works with the active theme and other catalog-related extensions.

## Development principles

Keep changes focused and reviewable. Separate interface changes from filtering logic, preserve existing compatibility contracts, and avoid claiming performance, security, or compatibility improvements without evidence.

## Compatibility and support

Compatibility depends on the WordPress, WooCommerce, theme, and extension versions used by each store. Reproduce and validate integration issues in a staging environment before deploying changes to production.

## License

No explicit license file is currently included in this repository. Contact the repository owner before reusing or redistributing the code.
