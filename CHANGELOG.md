# Release Notes for Commerce Review

## Unreleased

- Fixed a potential XSS issue when rendering review comments in the control panel.
- Fixed review updates not being restricted to the customer who owns the review.
- Fixed the review window being applied backwards, which expired new reviews and reopened old ones.
- Reviews past their review window now fail validation with a message instead of returning an error page.

## 5.0.0

- Initial release
