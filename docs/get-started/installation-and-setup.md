# Installation and Setup

You can install Product Review via the plugin store, or through Composer.

### Craft Plugin Store

To install **Product Review**, navigate to the _Plugin Store_ section of your Craft control panel, search for `Product Review`, and click the _Try_ button.

### Composer

You can also add the package to your project using Composer and the command line.

1. Open your terminal and go to your Craft project:

```
cd /path/to/project
```

2. Then tell Composer to require the plugin, and Craft to install it:

```
composer require aodihis/product-review && php craft plugin/install product-review
```

### After installing

Reviews are not Craft elements. They do not appear in element queries, they have no field layout of their own, and you cannot add custom fields to them. Reach them through the methods in Available Custom Behavior instead.

{% hint style="info" %}
The plugin does nothing until you choose an order status. See Configuration.
{% endhint %}

### Uninstalling

```
php craft plugin/uninstall product-review
```

{% hint style="warning" %}
Uninstalling deletes all review data, and it cannot be recovered. Take a backup first if the reviews matter.
{% endhint %}
