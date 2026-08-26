# Product Review

Extend your commerce product review experience.

Product Review adds customer reviews to Craft Commerce. When an order reaches an order status you
choose, the plugin creates one review per product in that order. The customer fills it in with a
rating and an optional comment, and you read the results in the control panel.

## Documentation

**[Read the full documentation](docs/README.md)**

**Get Started**

| | |
| --- | --- |
| [Product Review](docs/get-started/product-review.md) | What the plugin does, and a four step quick start |
| [Requirements](docs/get-started/requirements.md) | Craft, Commerce and PHP versions |
| [Installation and Setup](docs/get-started/installation-and-setup.md) | Plugin Store, Composer, uninstalling |
| [Configuration](docs/get-started/configuration.md) | Choosing the order status, the review window |

**Reviews**

| | |
| --- | --- |
| [Flow](docs/reviews/flow.md) | The lifecycle, the three statuses, the review window |
| [Review](docs/reviews/review.md) | Everything on a review, in Twig and PHP |
| [Available Functions](docs/reviews/available-functions.md) | `craft.productReview`, for fetching reviews anywhere |
| [Available Custom Behavior](docs/reviews/available-custom-behavior.md) | What the plugin adds to products and users, in Twig and PHP |
| [Submitting a review](docs/reviews/submitting-a-review.md) | The form, with working examples |
| [Displaying reviews](docs/reviews/displaying-reviews.md) | Product pages, stars, breakdowns |
| [Control Panel](docs/reviews/control-panel.md) | The section, filters, permissions, review panels |
| [Services](docs/reviews/services.md) | Calling the plugin from your own PHP |

## Requirements

This plugin requires Craft CMS 5.0.0 or later, Craft Commerce 5.0.0 and PHP 8.2 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Product Review”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require aodihis/product-review

# tell Craft to install the plugin
./craft plugin/install product-review
```

## Getting started

After installing, open **Settings**, then **Plugins**, then **Product Review**, and choose the order
status that should start a review. The plugin does nothing until this is set.

Then let an order reach that status, and give the customer somewhere to submit:

```twig
{% for review in currentUser.getWaitingToReviewItems() %}
  <form method="post">
    {{ csrfInput() }}
    {{ actionInput('product-review/review/save') }}
    {{ hiddenInput('id', review.id) }}

    <p>{{ review.product.title }}</p>

    <select name="rating" required>
      {% for i in 1..5 %}<option value="{{ i }}">{{ i }}</option>{% endfor %}
    </select>

    <textarea name="comment"></textarea>
    <button type="submit">Submit review</button>
  </form>
{% endfor %}
```

And show them on the product page:

```twig
{% for review in product.getReviews() %}
  <p>{{ review.rating }} out of 5</p>
  <p>{{ review.renderComment() }}</p>
{% endfor %}
```

See [Building a review form](docs/review-form.md) and
[Displaying reviews](docs/displaying-reviews.md) for the full picture.
