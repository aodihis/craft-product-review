# Installation

## Requirements

| Requirement | Version |
| --- | --- |
| Craft CMS | 5.0.0 or later |
| Craft Commerce | 5.0.0 or later |
| PHP | 8.2 or later |

Craft Commerce must be installed before Product Review. The plugin adds foreign keys to the Commerce
products and orders tables, so installing it without Commerce will fail.

## From the Plugin Store

Open your project's control panel, go to the Plugin Store, search for Product Review, and press
Install.

## With Composer

```bash
cd /path/to/my-project

composer require aodihis/product-review

./craft plugin/install product-review
```

## What installing creates

Reviews are not Craft elements. They do not appear in element queries, they have no field layout,
and you cannot add custom fields to them. Reach them through the methods in the
[Twig reference](twig-reference.md) instead.

## After installing

The plugin does nothing until you choose an order status in the settings. See
[Settings](settings.md).

## Uninstalling

```bash
./craft plugin/uninstall product-review
```

**All review data is deleted and cannot be recovered.** Take a backup first if the reviews
matter.
