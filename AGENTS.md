# AGENTS.md

Guidance for AI agents working in this repository.

## What this is

**Product Review** (`aodihis/product-review`) is a Craft CMS plugin that adds product reviews to
Craft Commerce. Customers get a review slot automatically when one of their orders reaches a
configured order status; they can then rate and comment on each purchased product, once, within a
configurable time window.

- Namespace: `aodihis\productreview\` → `src/`
- Plugin handle: `product-review` — this is also the `Craft::t()` translation category
- Requires PHP >= 8.2, `craftcms/cms ^5.0.0`, `craftcms/commerce ^5.0.0-beta3`
- Licensed proprietary; docs at https://aodihis.gitbook.io/product-review

## How it fits together

**Review lifecycle.** `Plugin::registerOnOrderStatusChange()` listens for
`OrderHistories::EVENT_ORDER_STATUS_CHANGE`. When an order reaches the handle stored in
`Settings::$orderStatusToReview`, `Reviews::createReviewForOrder()` creates one *empty* review row
per product in the order (variants are recorded in a side table, so one review covers all variants
of the same product bought together).

A review therefore has three states, derived — not stored:

| State | Meaning |
| --- | --- |
| `pending` | `updateCount === 0`, still inside the review window — awaiting the customer |
| `live` | `updateCount > 0` — the customer submitted it |
| `expired` | `updateCount === 0` and the window has passed |

`updateCount` is the hinge: it doubles as "has this been filled in" and "how many times has it been
edited". `Settings::$maxDaysToReview` (0 = unlimited) bounds the window.

**Storage.** Two tables, both prefixed `prorev_` (see `db/Table.php`): `prorev_reviews` and
`prorev_reviews_variants`. Reviews are plain models + Active Records — deliberately **not** Craft
elements. Reads go through `craft\db\Query` in `services/Reviews.php`, not the Active Record.

**Surfaces.**
- CP section at `/product-review`, routes registered in `Plugin::registerCpRules()`, backed by
  `ReviewCpController` and Craft's `VueAdminTable`.
- Front-end Twig via `craft.productReview` → `services/FrontEnd.php`.
- Behaviors: `ProductBehavior` (`product.getReviews()`, `product.getRatingCountInList()`),
  `UserBehavior` (`user.getReviewHistory()`, `user.getWaitingToReviewItems()`), and
  `ProductQueryBehavior`, which joins an average-rating subquery into *every* product query so
  `averageRating` works as a table attribute and sort option.
- Public write endpoint: `ReviewController::actionSave()`.

## Local development

The plugin is developed against the Craft install at `C:\workspace\craft-cms\cms`, which runs under
DDEV. **Docker Desktop must be running first.**

The plugin is mounted into the container and required as a Composer path repository:

- Mount: `.ddev/docker-compose.plugin-mount.yaml` maps this repo to `/home/shared/product-review`
- Repo entry: `repositories.product-review` → `path` → `/home/shared/product-review`
- Required as `aodihis/product-review:@dev`

Because it is a path repository, **edits here are live in the container** — no reinstall needed.

```bash
cd C:\workspace\craft-cms\cms
ddev start
ddev exec php craft plugin/install product-review   # first time only
```

Notes that will save you time:

- Run `ddev` from **PowerShell, not Git Bash** — Git Bash rewrites container paths
  (`/home/shared/…` becomes `C:/Program Files/Git/home/shared/…`) and the command fails.
- The Craft project pins `policy.advisories.ignore: ["dompdf/dompdf"]`. Composer 2.9+ blocks
  advisory-flagged packages, and Commerce, Freeform and Formie all depend on dompdf, so without this
  the project cannot resolve at all. Do not remove it.
- `php craft --version` is not a command; use `php craft version`.
- Site: https://cms.ddev.site · CP: https://cms.ddev.site/admin/product-review

### Quality tooling

```bash
composer check-cs    # ecs
composer fix-cs      # ecs --fix
composer phpstan     # NOTE: no phpstan.neon is committed; this will not run as-is
```

To run PHPStan, write a config including `vendor/craftcms/phpstan/phpstan.neon` and scanning
`vendor/craftcms/cms/src` + `vendor/craftcms/commerce/src`. Level 8 currently reports 53 issues,
level 5 reports 8 — treat new findings as signal, not the existing baseline.

## Conventions

- Services are registered in `plugin/Services.php` and reached via `Plugin::getInstance()->getReviews()`.
- Models are `craft\base\Model`; validation lives in `defineRules()`, never `rules()`.
- The `Review` model's `comment` is **not** in the `safe` rule, so `setAttributes()` drops it —
  `Reviews::_buildReviewModel()` reassigns it by hand. Keep that in mind before "simplifying" it.
- Use the `product-review` translation category for all user-facing strings, and never interpolate
  values into the translation *key* — pass them as params.
- Reviewer-supplied comment text is untrusted. It is stored raw, so sanitize at every render site
  (`|purify` in Twig, `Craft.escapeHtml()` in CP JavaScript). Craft's `VueAdminTable` writes cell
  content via `innerHTML`, so a table column callback is an HTML sink even without `|raw`.

## Always update the changelog

Every change gets an entry in `CHANGELOG.md` — **one short single-line sentence**, no exceptions.

The commit message can be as detailed as the change deserves (several sentences, bullet points,
context, rationale). The changelog is the opposite: it is read by people scanning releases, so
compress the whole change into a single line.

```
✅  - Fixed a potential XSS issue when rendering review comments in the control panel.

❌  - Fixed an XSS issue.
      - Review comments were rendered with `|raw` in `_view.twig`.
      - The CP table column also wrote them via `innerHTML`.
      - Both now sanitize the comment before rendering.
```

Add entries under the topmost version heading, matching the existing
`# Release Notes for Commerce Review` / `## <version>` format and the `- ` bullet style already in
the file.

## Watch out for

There is a standing audit in `issues.md` (git-excluded, local only) covering known bugs — a broken
authorization check in `ReviewController`, inverted review-window logic in `Review::getStatus()` /
`getIsEditable()`, in-place `DateTime` mutation, MySQL-only SQL, and missing CP permissions. Read it
before changing those areas so you do not "fix" something into a known trap, and update it when you
resolve an entry.
