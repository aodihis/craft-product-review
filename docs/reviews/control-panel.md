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

### Review panels

The reviews list shows everything at once. Often you want the opposite: the reviews for the one product, order or customer already open in front of you.

The plugin provides three read-only panels for that. They are field layout UI elements, so they are not shown until you add them, and you decide where they sit.

| Panel | Where the layout lives | Shows |
| --- | --- | --- |
| Product Reviews | Commerce → Settings → Product Types → a type → Product Fields | Submitted reviews of that product |
| Order Reviews | Commerce → Settings → Order Fields | Every review that order asked for, in any state |
| Customer Reviews | Settings → Users → User Fields | Every review that customer has been asked for, in any state |

Each panel appears in the field layout designer under **UI Elements**, alongside Craft's own Heading and Tip elements. Drag it into a tab and save.

Each panel is only offered to the layout it belongs to, so the Product Reviews panel does not appear on the variant field layout. You can add a panel to a single product type and leave the others alone.

A panel lists up to 20 reviews, newest first, and reports the full count below the table when there are more. Products and customers are shown as element chips linking to their own edit pages.

Every panel shows the rating, the comment, and the date. Beyond that they differ, because repeating what is already on the page is noise:

* **Product Reviews** adds the reviewer, and lists submitted reviews only. Every purchase creates an empty review up front, so including un-submitted ones would pad the table with rows no customer has written.
* **Order Reviews** and **Customer Reviews** add the product and a status, and include every state. The pending and expired rows are the useful part here: they show what was asked for and never written.

**View** opens the review's detail page. It appears on submitted reviews only, since a pending review has no detail page to open.

{% hint style="info" %}
The panels are covered by the same **View product reviews** permission. Someone who can edit a product but does not hold it sees no panel, rather than an empty one.
{% endhint %}

### Product ratings in the products list

The plugin adds a **Rating** column to the Commerce products list, showing each product's average rating.

To show it, open the products list, press the settings gear at the top of the table, and add Rating to the visible columns. You can also sort the list by it. Products with no reviews show `0`.
