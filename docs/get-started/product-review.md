# Product Review

Product Review is a plugin that extends Craft Commerce's ability to allow customers to give a review for each product they bought after completing the order.

Reviews are created for the customer automatically. When an order reaches an order status you choose, the plugin creates one review per product in that order, and the customer fills it in with a rating and an optional comment.

{% hint style="info" %}
Note: Product review only supports Products with variants as purchasable.
{% endhint %}

### Quick start

Four steps to a working review on a page.

1. **Choose the order status that starts a review.** Go to Settings → Plugins → Product Review and pick a status such as Completed. Nothing happens until this is set.
2. **Let an order reach that status.** The plugin creates one review per product in the order, owned by the customer.
3. **Give the customer somewhere to submit it**, using `currentUser.getWaitingToReviewItems()`.
4. **Show the reviews on the product page**, using `product.getReviews()`.

```twig
{% for review in product.getReviews() %}
  <article>
    <p>Rating: {{ review.rating }} out of 5</p>
    <p>{{ review.renderComment() }}</p>
  </article>
{% endfor %}
```

That is the whole loop. The rest of this documentation covers the details and the cases that come up on a real store.
