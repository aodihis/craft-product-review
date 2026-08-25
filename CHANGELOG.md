# Release Notes for Commerce Review

## Unreleased

> **⚠ Action required after updating:** this release adds a “View product reviews” permission that is
> now required to open the Product Review section and use its search endpoints. Admins are unaffected,
> but **any existing non-admin user or group that could previously see reviews will lose access until
> you grant them this permission** under Settings → Users → *(group)* → Permissions.

- Fixed a potential XSS issue when rendering review comments in the control panel.
- Fixed review updates not being restricted to the customer who owns the review.
- Fixed the review window being applied backwards, which expired new reviews and reopened old ones.
- Reviews past their review window now fail validation with a message instead of returning an error page.
- Fixed review queries relying on MySQL-only SQL, which prevented the plugin working on PostgreSQL.
- Fixed an unrecognised review status causing a database error instead of a clear one, and added support for querying `expired` reviews.
- Fixed un-submitted reviews appearing in a product's review list and ratings breakdown.
- Fixed guest orders creating reviews for customer accounts that can never sign in to submit them.
- Fixed the control panel erroring on reviews whose reviewer or product has been deleted.
- Added a “View product reviews” permission, now required for the control panel section and its search endpoints.

## 5.0.0

- Initial release
