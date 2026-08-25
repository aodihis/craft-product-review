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
| Reviewer | Press the button to open Craft's customer picker, then choose one |
| Product | Press the button to open Craft's product picker, then choose one |
| Rating | Choose an exact rating |

The reviewer and product filters are Craft's own element selectors, so they search, paginate and
handle sites exactly as they do everywhere else in the control panel.

Filters combine, so you can look at one customer's one star reviews of a single product.

Remove a chosen customer or product to clear that filter.

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

The permission covers the whole section, including the endpoint the table loads its rows from. A
user without it gets a 403 rather than an empty table.

It also covers the review panels described below. Someone who can edit a product but does not hold
this permission sees no panel on the product's edit page, rather than an empty one.

> **Upgrading from a version before permissions existed:** any non-admin user or group that could
> previously open the section will lose access until you grant them this permission.

## Review panels on product, order and customer pages

The reviews list shows everything at once. Often you want the opposite: the reviews for the one
product, order or customer already open in front of you.

The plugin provides three read-only panels for that. They are field layout UI elements, so they are
not shown until you add them, and you decide where they sit.

| Panel | Add it to | Shows |
| --- | --- | --- |
| Product Reviews | A product type's product field layout | Submitted reviews of that product |
| Order Reviews | The order field layout | Every review that order asked for, in any state |
| Customer Reviews | The user field layout | Every review that customer has been asked for, in any state |

### Adding a panel

Each panel appears in the field layout designer under **UI Elements**, alongside Craft's own Heading
and Tip elements. Drag it into a tab and save.

| Panel | Where the layout lives |
| --- | --- |
| Product Reviews | Commerce, then Settings, then Product Types, then a type, then the Product Fields tab |
| Order Reviews | Commerce, then Settings, then Order Fields |
| Customer Reviews | Settings, then Users, then User Fields |

Each panel is only offered to the layout it belongs to. The Product Reviews panel does not appear on
the variant field layout, or on an entry's, because there is nothing for it to look up there.

You can add a panel to a single product type and leave the others alone, which is worth doing when
only some of your catalogue is reviewable.

### What a panel shows

A table of up to 20 reviews, newest first. When there are more, a line below the table reports the
full count so a truncated list is not mistaken for the whole story.

Every panel shows the rating, the comment, and the date. Beyond that they differ, because repeating
what is already on the page is noise:

- **Product Reviews** adds the reviewer, and lists submitted reviews only. Every purchase creates an
  empty review up front, so including un-submitted ones would pad the table with rows no customer
  has written.
- **Order Reviews** and **Customer Reviews** add the product and a status, and include every state.
  The pending and expired rows are the useful part here: they show what was asked for and never
  written.

Products and customers are shown as Craft element chips, so they link to their own edit pages. A
review whose product or customer has been deleted shows "Removed product" or "Deleted user" instead
of a broken link.

**View** opens the review's detail page. It appears on submitted reviews only, since a pending
review has no detail page to open.

Panels are read only, like the rest of the section. Nothing here edits or deletes a review.

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
