# AGENTS.md

Guidance for AI agents working in this repository.

## What this is

**Product Review** (`aodihis/product-review`) is a Craft CMS plugin that adds product reviews to
Craft Commerce. Customers get a review slot automatically when one of their orders reaches a
configured order status; they can then rate and comment on each purchased product, once, within a
configurable time window.

- Namespace: `aodihis\productreview\` → `src/`
- Plugin handle: `product-review` — this is also the `Craft::t()` translation category
- Requires PHP >= 8.2, `craftcms/cms ^5.0.0`, `craftcms/commerce ^5.0.0`
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
- Field layout UI elements in `fieldlayoutelements/`, offered to product, order and user layouts by
  `Plugin::registerFieldLayoutUiElements()` based on the layout's `type`. They are UI elements
  rather than fields because reviews are written on the front end: there is nothing to fill in, and
  nothing to store on the element being edited.

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
composer phpstan     # level 5, configured in phpstan.neon
```

**PHPStan is clean at level 5. Keep it that way** — any finding is one you introduced, so fix it
rather than adding it to a baseline. Level 8 still reports pre-existing issues, mostly missing
iterable value types, so raising the level is a separate piece of work.

Do not run `composer fix-cs` on Windows without checking the diff first. Every file in the repo is
CRLF in the working copy, `.gitattributes` normalises to LF on commit, and ECS wants to rewrite all
of them. The result is a diff touching every file and burying the real change.

## Conventions

- Services are registered in `plugin/Services.php` and reached via `Plugin::getInstance()->getReviews()`.
- Models are `craft\base\Model`; validation lives in `defineRules()`, never `rules()`.
- Every attribute the plugin populates from a database row is in the `safe` rule, so
  `setAttributes()` handles the whole row. `comment` used to be missing, which silently dropped it
  and forced a manual reassignment afterwards. Add new attributes to that rule rather than
  reassigning them.
- Use the `product-review` translation category for all user-facing strings, and never interpolate
  values into the translation *key* — pass them as params.
- Reviewer-supplied comment text is untrusted, and is sanitized **twice** on purpose:
  - **On save**, `Reviews::saveReview()` runs it through `sanitizeComment()` (HTML Purifier, same
    config as Twig's `|purify`). This protects consumers the plugin does not control — site
    templates and JSON responses.
  - **On output**, every render site sanitizes again. Do not skip this on the grounds that saving
    already did it. Rows written before save-side sanitizing existed are still raw — no migration
    was run, deliberately — and escaping is context-dependent regardless: use `|purify` in Twig, but
    `Craft.escapeHtml()` in control panel JavaScript, where `VueAdminTable` writes column-callback
    output through `innerHTML` and truncates, so purified markup could be sliced mid-tag.

  Prefer the model's accessors over sanitizing by hand at each call site:

  | Context | Use |
  | --- | --- |
  | HTML, in a template | `{{ review.renderComment() }}` — purifies and returns `Twig\Markup`, so no `|raw` is needed |
  | Plain text (CSV, email, feeds) | `{{ review.plainComment }}` — strips tags *and* decodes entities |
  | Control panel JavaScript | `Craft.escapeHtml(...)` — `VueAdminTable` truncates, then writes via `innerHTML` |

  `plainComment` returns *decoded* text, so it must never be paired with `|raw`: a comment stored as
  `&lt;script&gt;` comes back as `<script>`. Twig escapes it by default, which is what makes
  `{{ review.plainComment }}` safe.

  Note purifying stores HTML, so a bare `&` is saved as `&amp;` — correct as HTML, visible as an
  entity anywhere the comment is treated as plain text, which is what `plainComment` is for.

## Never remove a public API outright — deprecate it first

Anything a site can call is public API, and removing it breaks their templates on update. That
includes more than it looks like:

- service methods on `Reviews` (reachable as `Plugin::getInstance()->getReviews()`)
- model accessors used from Twig — `review.status`, `review.plainComment`, `review.renderComment()`
- behavior methods — `product.getReviews()`, `product.getRatingCountInList()`,
  `user.getReviewHistory()`, `user.getWaitingToReviewItems()`
- the `craft.productReview` variable and everything on it
- plugin settings names, since they are stored in project config

To retire one, deprecate it for at least one minor release before deleting it:

1. **Keep it working.** Leave the old method in place and forward to the replacement — never leave
   a deprecated method behaving differently from the one it delegates to.
2. **Mark it in the docblock**, naming the version and the replacement:
   `@deprecated in 5.1.0. Use [[renderComment()]] instead.`
3. **Log it at runtime**, so it appears in the control panel's Deprecation Warnings utility rather
   than only being noticed by someone reading source:

   ```php
   Craft::$app->getDeprecator()->log(__METHOD__, 'Reviews::oldMethod() has been deprecated. Use Reviews::newMethod() instead.');
   ```

   `log()` takes a unique key first — `__METHOD__` is the right one, so repeated calls collapse into
   a single warning.
4. **Say so in the changelog**, naming both sides: *"Deprecated `x`. Use `y` instead."*
5. **Delete it in the next major version**, not before.

Craft Commerce's own `LineItems::createLineItem()` is a good worked example of all four steps.

The exception is an API that has never shipped. Renaming something added earlier in the same
unreleased batch is just editing an unreleased change — no deprecation cycle, since nothing outside
the repo can be calling it yet. Check whether it appears under a released heading in `CHANGELOG.md`
before assuming it is safe to rename.

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
`# Release Notes for Product Review` / `## <version>` format and the `- ` bullet style already in
the file.

### Version headings

Craft requires the format `## X.Y.Z - YYYY-MM-DD`. **Any level-two heading that does not match is
ignored, along with every entry beneath it**, so a heading like `## Unreleased` means the whole
release is invisible in the Plugin Store and the Updates utility.

While work is unreleased, `## Unreleased` is the right heading precisely *because* Craft ignores it.
Notes for a version nobody can install yet should not appear in anyone's update screen.

**Renaming it to `## X.Y.Z - YYYY-MM-DD` is part of cutting the release**, alongside deciding the
version number. Forgetting that step is how a whole release ends up invisible, so check the heading
before tagging.

### The one exception: changes that need action after updating

If a change means an existing site **must** do something after updating (grant a new permission, run
a command, update a template), add a GitHub-style alert directly under the version heading, above
the bullets, in addition to the one-line entry:

```markdown
## 5.0.1 - 2026-08-25

> [!IMPORTANT]
> Add the “View product reviews” permission to any user group that needs access to the Product
> Review section.

- Added a “View product reviews” permission, now required for the control panel section.
```

Craft supports `> [!NOTE]`, `> [!IMPORTANT]` and `> [!WARNING]`, styles them in the Plugin Store and
the Updates utility, and automatically expands any update containing one.

Pick the level honestly. `[!IMPORTANT]` is for something the site owner must do to succeed, which
covers almost every case here. `[!WARNING]` is for genuine risk, and using it for routine
housekeeping makes an ordinary update look alarming.

Keep the note to the action itself, in one sentence. Not what broke, not who is affected, not why.
That belongs in the commit message. A reader scanning an update list wants the instruction.

## Always update the documentation

`docs/` is the manual a site owner reads. Anything that changes what they can do, or how they do it,
belongs there in the same commit as the code. A feature that only exists in the changelog is a
feature nobody finds.

Update the docs when a change adds or alters:

- a **feature** or a control panel surface, such as the review panels in `docs/control-panel.md`
- a **setting** — the name, what it does, its default, or what happens at its edges
- a **Twig method, behavior method, or service method** — `docs/twig-reference.md` and
  `docs/php-api.md` list these individually, so a new one needs a new entry
- **behaviour someone might already depend on**, even when no signature changed

Two rules that are easy to miss:

- **Rewrite what a change made wrong, do not only append.** Rebuilding the control panel filters
  meant the old "type two or more characters to search" description was describing something that no
  longer existed. Grep `docs/` for the thing you touched before assuming only an addition is needed.
- **A removal is a documentation change too.** Deleting an endpoint or a method means deleting or
  correcting the paragraph that promised it.

This is separate from the changelog, and does not replace it. The changelog says *what changed in
this release* in one line; the docs say *how the thing works* for someone who was not watching.
A user-facing change usually needs both, and adding a setting needs `docs/settings.md` as well as
the changelog line.

## Watch out for

There is a standing audit in `issues.md` (git-excluded, local only) covering known bugs — a broken
authorization check in `ReviewController`, inverted review-window logic in `Review::getStatus()` /
`getIsEditable()`, in-place `DateTime` mutation, MySQL-only SQL, and missing CP permissions. Read it
before changing those areas so you do not "fix" something into a known trap, and update it when you
resolve an entry.
