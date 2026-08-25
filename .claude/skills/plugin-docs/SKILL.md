---
name: plugin-docs
description: How to write and revise the Product Review documentation in docs/. Use whenever adding, editing, restructuring, or reviewing anything under docs/, when a code change needs documenting, or when syncing docs to GitBook.
---

# Writing the Product Review docs

## Who you are writing for

A developer wiring this plugin into their Craft Commerce site. They want to get something working
in their templates and their control panel. They are **not** working on the plugin, and they should
never need to understand how it is built.

Every page should answer "how do I use this on my store". If a paragraph only makes sense to someone
reading `src/`, it does not belong.

## The model to follow

Match the Verbb plugin docs, for example
<https://verbb.io/craft-plugins/wishlist/docs/get-started/installation-setup>. That style is the
target because Craft developers already read it and know where to look.

What that means concretely:

**Structure.** Group pages into a small number of named sections, and keep each page short and
single-purpose. Verbb's grouping is worth copying:

| Section | Holds |
| --- | --- |
| Get Started | Installation & Setup, Requirements, Configuration |
| Feature Tour | What each concept is, in prose, one page per concept |
| Template Guides | Task-shaped pages: doing a specific thing in Twig |
| Developers | Reference: the objects, the available variables, the services |

**One H1 per page**, matching the page title. `##` for sections. Reach for `###` only inside a
reference table, and prefer splitting the page instead.

**Open with one sentence** saying what the page is for. No preamble, no "in this guide we will".

**Numbered steps for anything procedural**, one action per step, with the command in its own fenced
block:

```
1. Open your terminal and go to your Craft project:

   cd /path/to/project

2. Then tell Composer to require the plugin, and Craft to install it:

   composer require aodihis/product-review && php craft plugin/install product-review
```

**Configuration pages show the whole config file first**, with the real defaults, then explain the
options as a bulleted list of `` `option` - what it does ``. Finish with a short Control Panel
section saying the same settings are editable at Settings → Product Review.

**Every example must run.** Copy it into a template and check it before shipping the page.

**Second person, present tense, imperative.** "Add the permission", not "the permission should be
added" or "we can add the permission".

## Never document

These are hard rules, not preferences.

- **Anything unreleased or unfinished.** No planned settings, no roadmap, no "fixed in code for now,
  making it configurable is planned", and no examples using an API still being decided. Documenting
  a half-finished feature invites sites to build on it, and then it cannot be changed. If it is not
  shipped and settled, it does not exist as far as `docs/` is concerned.
- **Databases.** No table names, no columns, no schema, no SQL, not even for troubleshooting.
  Storage is an implementation detail, and naming it turns it into something people depend on.
- **Internal class surface.** No validation rules, no settings model, no permission constants, no
  table constants, no records or Active Records. A site grants a permission through the control
  panel, so document the control panel. Nobody integrating needs `Plugin::PERMISSION_VIEW_REVIEWS`.
- **Internal counters and derived state backing an unfinished feature**, such as `updateCount` and
  `isEditable`. Their meaning changes as the feature settles.
- **Craft's own events and APIs.** Craft documents those. A copy here goes stale.

Prefer plain language over internal vocabulary. "The review remains, with a status of expired" says
the same thing as "the row stays in the database", without teaching anyone the schema.

## Where a thing goes

Split by **who can call it**, not by which language the example happens to use. Putting an element
behavior under a "Twig reference" heading tells a PHP reader it is unavailable, which is wrong.

| Available from | Page |
| --- | --- |
| Twig and PHP, on a product or user | `docs/product-and-user-methods.md` |
| Twig and PHP, on a review | `docs/review-object.md` |
| Twig only | `docs/twig-variable.md` |
| PHP only | `docs/php-api.md` |

When something works in both, show both forms.

## Keeping it true

- **Verify against the code before writing.** Confirm the method exists and the signature and
  defaults match. Wrong docs are worse than missing docs.
- **Rewrite what a change made wrong, do not only append.** Grep `docs/` for whatever you touched.
  Rebuilding the control panel filters left a page describing a search box that no longer existed.
- **A removal is a documentation change.** Deleting an endpoint or a method means deleting or
  correcting the paragraph that promised it, and any link to it.
- **Check every internal link still resolves** after renaming or removing a page.

## GitBook

The published site at <https://aodihis.gitbook.io/product-review> mirrors `docs/`. It is edited
through a change request, never directly:

1. `create_change_request` on the space
2. `updateChangeRequestContent` for the page edits
3. `submit_or_merge_change_request` to publish

**Merging publishes to a public site, so ask before merging.** Leave the change request open for
review unless told otherwise.
