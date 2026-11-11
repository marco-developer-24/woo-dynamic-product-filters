# Legacy Identity and Compatibility Boundary

## Purpose

This document separates the plugin's public identity from identifiers inherited from its original PolyCor implementation.

The current public attribution is **Marco Rubino** and the author website is **https://marcodeveloper.online**. The repository is named `woo-dynamic-product-filters`.

That branding change does not make the old technical identifiers safe to rename. Existing WordPress installations, widgets, database records, custom snippets, themes, and integrations may depend on them.

This is an initial source-code inventory, not proof that every identifier is used by an external installation. Before a migration, inspect real installations and search known integrations.

## Compatibility inventory

### 1. Persistent database contract

| Identifier | Location / use | Classification | Migration policy |
| --- | --- | --- | --- |
| `{$wpdb->prefix}polycor_cat_attrs` | `PolyCor_DB::__construct()`; created by `create_tables()`; removed by uninstall routine | **Critical persistent data** | Keep the existing table name during ordinary upgrades. A future rename requires an explicit copy/migrate/verify plan and a rollback path. Never drop the old table as part of a branding-only change. |

The table stores category-to-attribute mappings, including category ID, attribute ID, attribute slug, display order, and active state. Its rows are user configuration, not disposable cache.

### 2. WordPress options and transients

| Identifier | Observed use | Classification | Migration policy |
| --- | --- | --- | --- |
| `polycor_version` | Written on activation; deleted by uninstall | Persistent option | Continue reading/updating it during transition, or migrate it deliberately. |
| `polycor_settings` | Deleted by uninstall; no corresponding write was found in the inspected source | Potential legacy option | Preserve awareness; verify whether existing installations contain it before any cleanup. |
| `polycor_admin_notices` | Admin notice transient | Ephemeral state | Safe to replace only if all producers and consumers are updated together. |
| `polycor_attr_{attribute}_cat_{category}` | Attribute-term count cache in `PolyCor_Attribute_Manager` | Ephemeral cache | Can be invalidated/rebuilt; changing the prefix may cause a temporary cold cache, not data loss. |

The uninstall routine currently drops the mapping table, deletes options/transients, and calls `wp_cache_flush()`. This destructive behavior must be reviewed separately before publishing a broadly distributed plugin. Uninstall must not be used as a migration mechanism.

### 3. Shortcodes and widget identity

| Identifier | Evidence | Classification | Migration policy |
| --- | --- | --- | --- |
| `polycor_filter_groups` | Registered by `PolyCor_Filter_Renderer` | Public shortcode | Keep as a compatibility alias if a replacement shortcode is introduced. |
| `polycor_active_filters` | Registered by `PolyCor_Filter_Renderer`; used by the widget | Public shortcode | Keep as a compatibility alias. |
| `polycor_dynamic_chips` | Registered by `PolyCor_Filter_Renderer` | Public shortcode | Keep as a compatibility alias. |
| `polycor_filters` | Shown in the admin mapping view as the basic shortcode, but no registration was found in the inspected renderer | **Documentation/implementation discrepancy** | Do not promise support until registration and behavior are verified. Resolve this discrepancy in a separate functional change. |
| `polycor_filters_widget` | Widget base ID passed to `WP_Widget` | Persisted widget identity | Do not change the base ID casually. WordPress stores widget instances using it. A replacement needs widget-option migration or a compatibility widget. |
| `PolyCor_Filters_Widget` | Registered with `register_widget()` | PHP class / integration surface | Keep available while the old widget is supported; introduce a new class only with an explicit compatibility strategy. |

Shortcodes may be embedded in page builders, posts, templates, and theme files. A source-code rename cannot discover all those uses.

### 4. AJAX actions and nonce contracts

Admin AJAX actions observed in `PolyCor_Admin_Page`:

- `wp_ajax_polycor_delete_mapping`
- `wp_ajax_polycor_update_order`
- `wp_ajax_polycor_get_frequent_attributes`

Their JavaScript callers send the matching action names. The admin nonce action is `polycor_admin_action`; the request field is `nonce`.

Additional frontend AJAX action names are declared in `PolyCor_Ajax_Handler`:

- `wp_ajax_polycor_filter_products`
- `wp_ajax_nopriv_polycor_filter_products`
- `wp_ajax_polycor_get_terms`
- `wp_ajax_nopriv_polycor_get_terms`

The frontend handler checks nonce action `polycor_filter_nonce`.

**Runtime caveat:** the main bootstrap includes the AJAX handler file, but the inspected initialization path does not instantiate `PolyCor_Ajax_Handler`. Therefore, the presence of these hook declarations in the class does not prove that the frontend AJAX endpoints are currently registered at runtime. Do not treat them as verified working endpoints without an integration test.

If action names are ever replaced, register both old and new actions for a transition period, validate authorization and nonces on both paths, and update the JavaScript callers in the same change.

### 5. Admin route and form fields

The WooCommerce submenu slug is `polycor-filters`. Admin forms and handlers also use:

- `polycor_action`
- `polycor_nonce`
- nonce action `polycor_admin_action`
- settings-error group/code `polycor_messages` / `polycor_message`

These are internal WordPress integration points and may be referenced by custom admin tooling. Keep stable until a coordinated migration is justified.

### 6. PHP symbols and bootstrap globals

The inspected source declares globally named symbols including:

- Classes: `PolyCor_DB`, `PolyCor_Attribute_Manager`, `PolyCor_Category_Mapper`, `PolyCor_Filter_Renderer`, `PolyCor_Ajax_Handler`, `PolyCor_Dynamic_Filters`, `PolyCor_Admin_Page`, `PolyCor_Filters_Widget`, `Polycor_Chips`.
- Functions: `polycor_init()`, `polycor_load_files()`, `polycor_check_woocommerce()`, `polycor_activate()`, `polycor_deactivate()`.
- Constants: `POLYCOR_VERSION`, `POLYCOR_PLUGIN_DIR`, `POLYCOR_PLUGIN_URL`, `POLYCOR_PLUGIN_BASENAME`.
- Global: `$GLOBALS['polycor_renderer']`.

These are not necessarily documented public APIs, but PHP has no package-private visibility for global symbols. Third-party snippets can call them. Avoid bulk renaming; introduce namespaced/internal replacements with compatibility wrappers only when there is a concrete benefit and tests cover both paths.

### 7. Translation domain, files, and frontend selectors

- Text domain: `polycor-filters` (also used by translation calls in PHP).
- Main plugin file: `polycor-dynamic-filters.php`.
- Asset filenames include `polycor-admin.css`, `polycor-frontend.css`, `polycor-chips.css`, `polycor-admin.js`, `polycor-frontend.js`, and `polycor-chips.js`.
- CSS/HTML classes use the `polycor-` prefix, including filter and chip elements.

The text domain, plugin main-file path, asset handles/paths, and CSS selectors may be relied on by language packs, caches, child themes, custom CSS, or deployment scripts. Treat them as compatibility-sensitive until their consumers are understood.

## Proposed migration policy

1. **Branding layer:** keep the public plugin name, author, author URL, description, screenshots, and README independent from legacy implementation names.
2. **Compatibility layer:** preserve persistent storage names, widget IDs, shortcodes, AJAX actions, and established admin routes while the plugin is adopted under its new identity.
3. **Internal refactoring:** rename private implementation details only when it improves maintainability. Prefer namespaced classes and a single bootstrap boundary over global search-and-replace.
4. **Dual registration:** where a public identifier must change, register the old and new identifier to the same implementation for a defined deprecation period.
5. **Data migration:** for any option/table rename, use an idempotent migration with source checks, destination verification, error handling, and rollback guidance. Never delete source data before verification.
6. **Deprecation:** document old identifiers, announce a removal window, and emit a development-only notice where appropriate. Do not flood production logs.
7. **Removal:** remove compatibility aliases only in a major release, after usage evidence and a documented upgrade path.

## Required verification before implementation changes

- Activate and upgrade from a copy of a site that already has category/attribute mappings.
- Confirm mapping rows remain intact before and after the upgrade.
- Confirm existing widget instances still render.
- Confirm every registered shortcode still renders; separately investigate the undocumented `[polycor_filters]` discrepancy.
- Verify admin AJAX actions, capability checks, nonce checks, and JavaScript callers.
- Verify frontend AJAX only after confirming the handler is actually instantiated.
- Confirm existing page-builder content and custom CSS continue to work.
- Test uninstall separately and explicitly confirm the expected data-retention policy.
- Run PHP linting and WordPress/WooCommerce integration tests.

## Scope of this document

This change is documentation-only. It does not rename classes, functions, constants, hooks, shortcodes, options, transients, database tables, files, asset handles, CSS selectors, or the text domain. It does not change runtime behavior.

The inventory is based on the repository source inspected for this document. It cannot identify references stored in external themes, page-builder content, snippets, deployment scripts, or production databases.
