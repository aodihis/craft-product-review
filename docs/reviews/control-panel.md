# Control Panel

Product Review appears as **Product Review** in the main control panel navigation, at `/admin/product-review`.

### The reviews list

A table of every submitted review, showing the product, rating, comment, and reviewer. Un-submitted reviews are not listed, since there is nothing to read yet.

Three filters sit above the table, and they combine.

| Filter | Behaviour |
| --- | --- |
| Reviewer | Press the button to open Craft's customer picker, then choose one |
| Product | Press the button to open Craft's product picker, then choose one |
| Rating | Choose an exact rating |

Press **View** on any row for the detail page, which shows the rating, the comment, the reviewer, the product, the variants bought, and the dates.

A review whose product or customer has been deleted shows "Removed product" or "Deleted user" instead of a broken link.

{% hint style="info" %}
Reviews cannot be edited or deleted from the control panel. There is no approval queue, so a submitted review is live immediately.
{% endhint %}

### Permissions

Access is controlled by a single permission, **View product reviews**, under a **Product Review** heading in the permissions list.

Assign it at Settings → Users → a group → Permissions, or per user on the user's own Permissions tab. Admin accounts hold every permission automatically and do not need it assigned.

{% hint style="warning" %}
Upgrading from a version before this permission existed: any non-admin user or group that could previously open the section will lose access until you grant it.
{% endhint %}

### Product ratings in the products list

The plugin adds a **Rating** column to the Commerce products list, showing each product's average rating.

To show it, open the products list, press the settings gear at the top of the table, and add Rating to the visible columns. You can also sort the list by it. Products with no reviews show `0`.

### Review panels on products, orders and users

You can show a read-only list of reviews on the edit screen for a product, an order, or a customer, by adding it to the relevant field layout.

1. Go to the field layout you want it on:

   - Products: Commerce → System Settings → Product Types → a product type → Product Fields
   - Orders: Commerce → System Settings → Order Fields
   - Users: Settings → Users → User Fields

2. Drag the panel from the UI elements at the right of the designer into a tab, and save the layout. Each layout is offered the one panel that suits it:

   | Layout | Panel | Lists |
   | --- | --- | --- |
   | Product | Product Reviews | Submitted reviews for this product, with the reviewer, rating, comment and date |
   | Order | Order Reviews | Every review this order asked for, submitted or not, with the product, rating, comment, status and date |
   | User | Customer Reviews | Every review this customer has been asked for, submitted or not, with the product, rating, comment, status and date |

The panel lists ten reviews at a time, with paging controls when there are more. Long comments are shortened behind a **Show more** toggle.

{% hint style="info" %}
The panel is read-only, and it respects the **View product reviews** permission. A user who can edit a product but does not hold that permission sees no panel on it.
{% endhint %}
