# Release Notes for Product Review

## 5.1.0 - 2026-08-25

> [!IMPORTANT]
> Add the “View product reviews” permission to any user group that needs access to the Product
> Review section.

- Fixed a potential XSS issue when rendering review comments in the control panel.
- Review comments are now sanitized when saved, in addition to when they are rendered.
- Added full documentation under `docs/`, linked from the readme.
- Added `review.renderComment()`, which renders a comment as sanitized HTML without needing `|raw`.
- Fixed a review staying editable after it had already been submitted.
- The control panel reviews table now shows comments as plain text instead of their stored markup.
- Added `review.plainComment` (`getPlainComment()`) for rendering a comment outside HTML, such as in an export or email.
- Fixed review updates not being restricted to the customer who owns the review.
- Fixed the review window being applied backwards, which expired new reviews and reopened old ones.
- Reviews past their review window now fail validation with a message instead of returning an error page.
- Fixed review queries relying on MySQL-only SQL, which prevented the plugin working on PostgreSQL.
- Fixed an unrecognised review status causing a database error instead of a clear one, and added support for querying `expired` reviews.
- Fixed un-submitted reviews appearing in a product's review list and ratings breakdown.
- Fixed the control panel showing a blank author for reviews left by guest customers.
- Fixed the plugin’s translations never loading, as the translation file did not match the `product-review` category.
- Fixed the control panel’s reviewer and product filters returning unfiltered results, and mishandling search terms containing spaces or `&`.
- Fixed the control panel erroring on reviews whose reviewer or product has been deleted.
- Added a “View product reviews” permission, now required for the control panel section and its search endpoints.

## 5.0.0 - 2024-06-27

- Initial release
