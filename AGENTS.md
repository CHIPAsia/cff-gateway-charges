# AGENTS.md

This file provides guidance to AI coding agents when working with code in this repository.

## Project Overview

**CHIP FluentForms Gateway Charges** is a WordPress plugin that adds the CHIP
gateway charge to the amount collected by Fluent Forms. It hooks the CHIP for
Fluent Forms plugin and appends the charge as an extra product on the purchase.

- Text domain: `cff_gc`
- Prefix: `CFFGC_` (constants), `cff_` (functions)
- PHP floor: **7.4**
- Plugin file: `chip-fluent-forms-gateway-charges.php`

This plugin is distributed through GitHub only. It is **not** published to the
WordPress.org plugin directory and has no SVN release pipeline.

## Commands

```bash
phpcs --standard=phpcs.xml .          # MUST exit 0
```
There is no test suite and no build step in this repository.

## Architecture

```
chip-fluent-forms-gateway-charges.php   Entry point + charge calculation + hooks
uninstall.php                           Deletes the plugin option
includes/
  class-chip-ff-settings.php            Settings framework - page/section registry
  class-chip-ff-settings-page.php       Settings page renderer and save handler
  admin/global-settings.php             Global charge settings
  admin/form-settings.php               Per-form charge settings
```

## The charge calculation

`cff_get_settings()` resolves the values for a form. A form with
`form_customize_<id>` enabled reads its own `<key>_<id>` values; anything it
does not set uses the plugin's built-in default, which is what the field
descriptions document. The fee is then:

```
round( price * variable_rate + fixed_rate ), raised to minimum_fee if lower
```

- `variable_rate` is stored as `2200` for 2.2% and divided by 100000.
- All money values are in cents.
- **A configured `0` is a valid value** ("set 0 for RM 0.00"). The resolution
  guard compares against the empty string strictly; do not replace it with
  `empty()` or a loose `!= ''`, because loose comparison drops a non-string 0.
- **A per-form key left blank uses the built-in default, not the global
  value.** That is existing behaviour, documented in the field text.

## Hooks (external contract - do not rename)

| Hook | Handler |
| --- | --- |
| `ff_chip_create_purchase_params` | `cff_inject_gateway_charges` |
| `ff_chip_after_purchase_create` | `cff_inject_order_item` |

`ff_chip_handle_paid_data` is intentionally **not** registered;
`cff_reverse_total_amount()` is kept for it.

## The settings framework is shared

`class-chip-ff-settings.php` and `class-chip-ff-settings-page.php` are copies of
the framework used by `chip-for-fluent-forms` and `chip-for-paymattic`. Keep them
byte-identical apart from the text domain, which is `cff_gc` here. A behavioural
change belongs in all three repos, not just this one.

The framework replaced the vendored Codestar Framework, whose maintainer stopped
maintaining it. Never reintroduce it.

## Rules that are not obvious

- **The option name is `cff_gc`** and the stored value is a flat
  `field id => value` map. `uninstall.php` cannot use the constants, so it
  writes the name literally - keep the two in sync.
- **Never change the option name or shape.** Every saved setting lives there and
  there is no migration path.
- **Push fast-forward only - never `--force`.**
- Commit as `Wan Zulkarnain <wanzulkarnain69@gmail.com>`.
- Do not name deployment hosts, internal hosts or IPs in this repository.
