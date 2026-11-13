# UX Specification: Non-technical WooCommerce Admin Experience

**Status:** Proposed  
**Scope:** WordPress admin interface for PolyCor Dynamic Filters  
**Target user:** Store owner or store manager who understands products and categories but is not expected to understand WordPress internals, PHP, attribute taxonomies, slugs, or shortcodes.

## 1. Product UX objective

Enable a store manager to configure which product characteristics customers can filter by, for each product category, without needing developer assistance.

The interface must explain the user's task in store language. Technical implementation terms may remain available as secondary information, but must not be required to complete the primary workflow.

### Primary success criteria

A first-time user can:

1. Find the category they want to configure.
2. Understand whether filters are already configured for it.
3. Add and remove product characteristics.
4. Change the display order.
5. Save and understand whether the save succeeded.
6. Copy an existing configuration to another category without accidentally overwriting data.
7. Find clear instructions for displaying the filters in the storefront.

These are usability goals to verify with testing, not claims about current behavior.

## 2. Language and terminology

Use one consistent language throughout the interface. The initial interface language should be Spanish.

| Avoid as primary UI language | Use instead |
|---|---|
| Mapeo | Configuración de filtros |
| Atributo | Característica del producto |
| Slug | Identificador técnico (advanced details only) |
| Clonar | Copiar configuración |
| Categoría origen/destino | Copiar desde / Copiar a |
| Shortcode | Código para mostrar los filtros, with copy-and-paste instructions |
| Guardar mapping | Guardar configuración |

Use the actual WooCommerce attribute name as the selectable item label. Do not rename product data in a way that could make the store manager select the wrong attribute.

### Microcopy principles

- Describe the outcome, not the implementation.
- Use short, action-oriented labels.
- Explain unfamiliar concepts at the point of use.
- Avoid relying on tooltips for essential instructions.
- Do not use unexplained abbreviations or developer vocabulary.
- Confirm destructive or potentially overwriting actions with a clear description of their impact.

## 3. Information architecture

Keep the interface native to WordPress/WooCommerce admin. Do not introduce a separate SPA or a new navigation system.

### Main navigation

1. **Configuraciones** — list and manage category filter configurations.
2. **Ayuda** — explain how filters work and how to display them in the storefront.

The create and edit experiences should share the same form structure and components. The form changes its heading and context, not its interaction model.

Copying should be a contextual action from an existing configuration, not a peer workflow competing with create and edit in the main navigation.

## 4. Main screen: Configuraciones

### Purpose

Answer, immediately:

- Which categories have filter configurations?
- How many characteristics are configured for each?
- What can I do next?

### Required content

- Page title: **Configuraciones de filtros**
- Introductory sentence: **Elegí qué características pueden usar tus clientes para filtrar productos en cada categoría.**
- Primary action: **Configurar una categoría**
- Search field: **Buscar categoría**
- A list or table of configured categories.

### List columns / information

- Category name
- Number of selected characteristics
- Configuration status, only if status has a real and well-defined meaning
- Actions: **Editar**, **Eliminar**\n- Add **Copiar** only after the copy operation is implemented and tested.

Do not add dashboard metrics such as total configurations, total attributes, or category counts unless they are derived from real data and help users make a decision.

### Empty state

Title: **Todavía no configuraste filtros**

Body: **Elegí una categoría y agregá las características que tus clientes podrán usar para encontrar productos.**

Primary action: **Configurar la primera categoría**

### Search and no-results state

If the search returns no categories, show: **No encontramos categorías con ese nombre. Probá con otra búsqueda.**

Do not present an empty table without explanation.

## 5. Create and edit flow

### Step 1: Choose the category

Prompt: **¿En qué categoría querés configurar los filtros?**

Supporting text: **Los filtros que elijas se mostrarán para los productos de esta categoría.**

- Searchable category selector if the category list is long.
- Show the full category name and, where useful, its parent category.
- Prevent duplicate configurations for the same category if the data model does not support them.
- In edit mode, show the category as context and do not imply that it can be changed if it cannot.

### Step 2: Choose product characteristics

Prompt: **¿Qué características querés que tus clientes puedan filtrar?**

Supporting text: **Por ejemplo: marca, procesador, memoria RAM o color.**

The selection area must distinguish clearly between:

- Available characteristics.
- Selected characteristics.

Required behaviors:

- Search available characteristics.
- Add and remove characteristics.
- Show selected items in a separate, clearly labeled area.
- Show an explicit selected state; do not rely on color alone.
- Keep the selected count visible if it helps users understand progress.
- Handle the case where no attributes are available with an explanation and a route to WooCommerce product attributes.

Do not expose raw slugs in the primary selection UI. If technical identifiers are useful for support, put them in a secondary details area.

### Step 3: Set display order

Prompt: **¿En qué orden querés mostrar los filtros?**

Supporting text: **Este orden determina cómo aparecerán las características en la tienda.**

- Make the reorder affordance visible.
- Provide an alternative to drag-and-drop, such as move up / move down controls.
- Preserve the order when editing.
- Make the current position understandable without relying only on visual placement.

### Step 4: Review and save

Before saving, show a concise summary:

- Category being configured.
- Selected characteristics.
- Their display order.

Primary action: **Guardar configuración**

Secondary action: **Cancelar**

Do not use a generic **Submit** label.

### Save states

The interface must distinguish:

- Ready to save.
- Saving.
- Saved successfully.
- Save failed.
- Validation error.

Success message: **La configuración se guardó correctamente.**

Failure message: **No pudimos guardar los cambios. Revisá tu conexión e intentá nuevamente.** If the actual error provides a safe, actionable explanation, show it without exposing sensitive internals.

If saving fails, preserve the user's selections and order.\n\n**Important current behavior:** the existing save path replaces the complete set of configured characteristics for that category. The interface must warn users that saving changes replaces the previous selection, not appends to it. Do not introduce autosave or partial-save language unless the backend contract changes.

If there are unsaved changes and the user attempts to leave, warn them before discarding those changes.

## 6. Copy configuration flow

Use **Copiar configuración** rather than **Clonar**.

Entry point: contextual action on an existing category configuration.

Flow:

1. Identify the source category: **Copiar desde: [category]**.
2. Select the destination category: **¿En qué categoría querés usar esta configuración?**
3. Explain what will be copied: selected characteristics and their order.
4. Detect whether the destination already has a configuration.
5. If it does, do not overwrite silently. Explain the consequence and require an explicit confirmation.
6. Confirm completion and link to the destination configuration.

The exact overwrite/merge behavior must follow the backend contract. UX must not promise merge, replacement, or preservation until that behavior is confirmed in code.

## 7. Delete flow

Use **Eliminar configuración**.

Confirmation must identify the category and explain the consequence. The inspected delete path removes rows from the plugin's mapping table for the selected category; it does not call WooCommerce product or attribute deletion APIs. The confirmation can therefore explain that it removes this plugin's filter configuration, not products or WooCommerce attributes:

**¿Eliminar los filtros de [category]?**

**Esta acción quitará la configuración de filtros de esta categoría. No eliminará los productos ni sus características de WooCommerce.**

Only state that product data is preserved after confirming that the deletion handler actually behaves this way.

Provide cancel and destructive confirmation actions. Avoid relying on the browser's native confirm dialog as the only explanation.

## 8. Help and storefront instructions

The Help section should answer:

- What are product filters?
- What is a product characteristic / WooCommerce attribute?
- How do I create attributes if none are available?
- How do I display the filters in the storefront?
- Where do I place the provided shortcode or integration snippet?
- How can I verify that filters are visible?

The inspected renderer registers three shortcodes: `[polycor_filter_groups]`, `[polycor_active_filters]`, and `[polycor_dynamic_chips]`. The main filter group shortcode accepts `category_id`, `layout`, `show_count`, and `style` attributes. The Help content must explain which shortcode is appropriate for the intended placement and must not imply that all three are interchangeable.\n\nIf a shortcode is required, label it as a copyable code snippet and explain where it goes. Do not assume the store manager knows what a shortcode is.

Example wording:

**Mostrar los filtros en tu tienda**

1. Copiá el código que aparece abajo.
2. Pegalo en el bloque o área de tu página donde quieras mostrar los filtros.
3. Guardá la página y revisá la categoría en tu tienda.

The final instructions must match the plugin's actual rendering and shortcode behavior.

## 9. Accessibility and interaction requirements

- Every interactive control must have a programmatic accessible name.
- Keyboard users must be able to add, remove, and reorder selected characteristics.
- Focus must remain visible.
- Selected state must not be communicated by color alone.
- Status and save feedback must be announced to assistive technology.
- Form errors must be associated with the relevant control.
- Destructive actions must be understandable before confirmation.
- Respect reduced-motion preferences.
- Interactive targets should be comfortably usable in the WordPress admin context.

## 10. Responsive behavior

The admin UI must remain usable at narrow viewport widths.

- Avoid fixed two-column layouts that force horizontal scrolling.
- Stack available and selected characteristic panels when necessary.
- Keep primary actions visible and unambiguous.
- Allow long category and attribute names to wrap.
- Ensure tables have a deliberate narrow-screen presentation rather than accidental overflow.
- Do not hide essential actions only behind hover states.

## 11. Visual hierarchy

Prioritize in this order:

1. Current task and page context.
2. Main decision the user needs to make.
3. Primary action.
4. Supporting explanation.
5. Secondary and technical details.

Use a consistent component system for buttons, fields, notices, cards, tables, empty states, and destructive actions. Avoid additional layers of CSS overrides and inline styling.

Visual polish is subordinate to task clarity, predictable behavior, and accessibility.

## 12. Acceptance tests

A usability test participant who has not used the plugin before should be able to complete these tasks without verbal assistance:

1. Find the configuration for a named category.
2. Create a configuration for a category.
3. Add three characteristics.
4. Remove one characteristic.
5. Change the order using both pointer and keyboard interaction.
6. Save and identify the success state.
7. Copy a configuration to a category with no existing configuration.
8. Understand what happens when the destination already has a configuration.
9. Delete a configuration and understand what is and is not deleted.
10. Find instructions for displaying filters in the storefront.

Record task completion, errors, hesitation, and whether the participant understands the result. Do not treat visual preference as a substitute for task success.

## 13. Implementation sequence

### P0 — Confirm functional contracts

Before changing the interface, verify in code:

- How category configurations are stored and identified.
- Whether duplicate category configurations are possible.
- Exact copy behavior when the destination is empty or already configured.
- Exact delete behavior.
- Attribute availability and selection constraints.
- How filters are rendered in the storefront.
- Whether the shortcode is required and its exact syntax.
- Save and AJAX response contracts.

### P1 — Shared create/edit experience

Unify the create and edit form structure. Remove duplicated markup where practical while preserving current backend contracts.

### P2 — Main configuration list

Improve the category list, search, empty state, and contextual actions.

### P3 — Copy, delete, and help

Make secondary workflows explicit and safe.

### P4 — Accessibility and responsive verification

Test keyboard operation, focus, status announcements, narrow viewports, and reduced motion.

### P5 — Usability validation

Run the acceptance tasks with at least one non-technical store manager. Revise based on observed friction.

## 14. Out of scope

- Replacing WordPress admin with a SPA.
- Changing filter query logic or storefront behavior as part of a visual redesign.
- Changing persistence formats without a separate migration plan.
- Adding analytics or invented dashboard metrics.
- Hiding advanced functionality that existing users rely on.
- Claiming accessibility or usability compliance without testing.

## 15. Implementation guardrail

This specification defines the intended user experience. It does not authorize changing backend behavior by implication. Any mismatch between the desired UX and current functional contracts must be surfaced before implementation, with the trade-off documented.
