# TechJosse Commerce

A lightweight, fast-loading, SEO friendly WooCommerce e-commerce theme for
WordPress, with AJAX live search, a slide-in mini cart, and a one-click Cash on
Delivery "Order Now" popup.

## Requirements

| | |
|---|---|
| WordPress | 6.0+ |
| PHP | 7.4+ |
| WooCommerce | required (the theme does nothing without it) |

## What is inside

```
functions.php              Theme bootstrap: constants, defaults, asset enqueue, icons
style.css                  Theme header only, plus a minimal base reset
inc/customizer.php         Customizer panels, settings and the generated colour CSS
inc/admin-settings.php     Appearance → Theme Settings screen (no customizer needed)
inc/performance-seo.php     Head cleanup, meta tags, structured data, asset tweaks
inc/woocommerce-setup.php  WooCommerce integration: hooks, sale badge, single product
inc/ajax-handlers.php      AJAX live search, shipping options, COD quick order
inc/master-import.php      Master settings importer (master-form.json)
assets/css/main.css        All real styling
assets/js/main.js          All front-end behaviour
template-parts/            Header topbar, search, mini cart, mega menu, cards
woocommerce/               Template overrides (content-product.php)
images/                    Design reference screenshots used while building the theme
master-form.json           Master settings export used by Appearance → Import
```

## The sale / discount badge

A product on sale shows its discount as a red `-6%` pill, in the same design on
**every** page: product cards (shop archive, categories, home page grids,
related, up-sells, cross-sells), the single product image, the AJAX live search
results and the mini cart.

All of it comes from one helper, `techjossecom_sale_badge()` in
`inc/woocommerce-setup.php`, and one CSS rule, `.onsale.tj-sale-badge` in
`assets/css/main.css`.

Two things are worth knowing before editing that rule:

- WooCommerce ships its own `.woocommerce span.onsale` (an olive circle) and
  `.woocommerce ul.products li.product .onsale` (pinned top-right with a
  negative margin). Both are out-specified by the theme.
- The base badge selector is deliberately **not** prefixed with `.woocommerce`.
  That class is only added to `<body>` on WooCommerce pages, so a `.woocommerce`
  prefix would silently skip the front page, the header search panel and the
  mini cart.

## Colour palette

Colours are CSS custom properties on `:root` in `assets/css/main.css`
(`--tj-primary`, `--tj-accent`, `--tj-sale`, and so on). The primary, accent,
dark and secondary values are also editable in the Customizer, which writes them
back as an inline `:root` block.

## Adding a new theme setting

`Appearance → Theme Settings` edits the same theme mods the customizer uses,
so the site can be configured without opening the customizer at all. Every
field is a single array entry in `techjossecom_admin_fields()` in
`inc/admin-settings.php`:

```php
'my_new_setting' => array(
    'type'        => 'text',        // text|textarea|url|email|number|checkbox|image|select|color
    'label'       => __( 'My setting', 'techjossecom' ),
    'description' => __( 'Optional help text.', 'techjossecom' ),
    'section'     => 'banners',     // which group it appears in
),
```

Adding the entry is all that is needed: the form row, the save handling and
the sanitising are all driven from that array. New groups can be registered
through the `techjossecom_admin_sections` filter, and extra fields through the
`techjossecom_admin_fields` filter.

Every key must also have an entry in `techjossecom_defaults()` in
`functions.php`, so the theme falls back to a sensible value before anything
has been saved.

## Local development notes

- The `.qa/` folder holds the local QA harness (screenshots, Chrome profiles,
  probe scripts). It is git-ignored: it is ~60 MB of machine specific
  artefacts, not theme source.
- `images/` holds the design reference screenshots the theme was built from.
  They are tracked on purpose, so the design intent travels with the code.

## Workflow

```bash
# before you start work, get the latest
git pull

# after you finish work
git add -A
git commit -m "Short description of the change"
git push
```
