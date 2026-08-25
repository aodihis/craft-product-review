# Control panel

## Finding it

Product Review appears as **Product Review** in the main control panel navigation, once the plugin
is installed and you have permission to see it.

The direct URL is `/admin/product-review`.

## The reviews list

A table of every submitted review, showing the product, rating, comment, and reviewer. Un-submitted
reviews are not listed, since there is nothing to read yet.

Three filters sit above the table:

| Filter | Behaviour |
| --- | --- |
| Reviewer | Type two or more characters to search customers, then pick one |
| Product | Type two or more characters to search products, then pick one |
| Rating | Choose an exact rating |

Filters combine, so you can look at one customer's one star reviews of a single product.

Use the clear button beside a filter to reset it.

## Viewing a single review

Press **View** on any row. The detail page shows the rating, the comment, the reviewer, the product,
the variants bought, and the created and updated dates.

The reviewer and product are links into their own edit pages, when those records still exist. A
review whose product or customer has been deleted shows "Removed product" or "Deleted user" instead
of a broken link.

Reviews cannot be edited or deleted from the control panel. The detail page is read only.

## Permissions

Access is controlled by a single permission, **View product reviews**, under a **Product Review**
heading in the permissions list.

Assign it at **Settings**, then **Users**, then a group, then **Permissions**. Or per user, on the
user's own Permissions tab.

Admin accounts hold every permission automatically and do not need it assigned.

The permission covers the whole section, including the reviewer and product search endpoints behind
the filters. A user without it gets a 403 rather than an empty table.

> **Upgrading from a version before permissions existed:** any non-admin user or group that could
> previously open the section will lose access until you grant them this permission.

## Product ratings in the products list

The plugin adds a **Rating** column to the Commerce products list, showing each product's average
rating.

To show it, open the products list, press the settings gear at the top of the table, and add Rating
to the visible columns. You can also sort the list by it.

Products with no reviews show `0`.

## What the control panel cannot do

Worth knowing before planning a moderation workflow:

- Reviews cannot be edited, hidden, or deleted from the control panel
- There is no approval queue, submitted reviews are live immediately
- There is no export

Deleting a review currently means deleting the row from the `prorev_reviews` table directly.
