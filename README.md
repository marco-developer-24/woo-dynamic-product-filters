# Woo Dynamic Product Filters

A WordPress plugin for WooCommerce focused on dynamic product filtering and AJAX-powered filter rendering.

The goal is to make product discovery more responsive while keeping the filtering behavior tied to the store's actual catalog and configuration.

![WordPress](https://img.shields.io/badge/WordPress-Plugin-21759B?logo=wordpress&logoColor=white)
![WooCommerce](https://img.shields.io/badge/WooCommerce-Extension-96588A?logo=woocommerce&logoColor=white)

## Contents

- [Scope](#scope)
- [Installation](#installation)
- [Validation checklist](#validation-checklist)
- [Development principles](#development-principles)
- [Compatibility and support](#compatibility-and-support)
- [License](#license)

## Scope

- Dynamic product filtering for WooCommerce.
- AJAX-powered rendering of filter results.

This README intentionally describes only the documented scope. Supported filter types, configuration options, hooks, and integration details should be documented once verified against the implementation.

## Installation

1. Download or clone this repository.
2. Copy the plugin directory into `wp-content/plugins/`.
3. Activate the plugin from the WordPress administration panel.
4. Test the available filters on a development or staging site before using them in production.

## Validation checklist

Before deploying a change, verify:

- [ ] The plugin activates without PHP errors.
- [ ] Filters behave as expected with the store's product catalog.
- [ ] AJAX requests complete successfully and return the expected results.
- [ ] Empty results and unusual filter combinations are handled clearly.
- [ ] The interface remains usable on mobile and with keyboard navigation.
- [ ] The plugin works with the active theme and other catalog-related extensions.

## Development principles

Keep changes focused and reviewable. Separate interface changes from filtering logic, and avoid claiming performance, security, or compatibility improvements without evidence.

## Compatibility and support

Compatibility depends on the WordPress, WooCommerce, theme, and extension versions used by the store. Validate integrations in a staging environment before deploying to production.

## License

Check the repository for an explicit license before reusing or redistributing this code.
