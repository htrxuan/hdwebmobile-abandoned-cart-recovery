# HDWebmobile Abandoned Cart Recovery

Recovers abandoned WooCommerce carts with a scheduled email, correctly capturing the customer's email from both the Blocks and classic checkout.

- **WordPress.org:** https://wordpress.org/plugins/hdwebmobile-abandoned-cart-recovery/
- **Requires:** WordPress 6.9+, WooCommerce, PHP 7.4+
- **License:** GPLv2 or later

## Description

HDWebmobile Abandoned Cart Recovery watches your checkout and captures a visitor's email address the moment they type it in — before they ever place an order. If they leave without completing checkout, a recovery email goes out automatically after a delay you choose, optionally including a one-time discount code.

Many existing cart-recovery plugins were built before the WooCommerce Checkout Block existed and simply never learned to read from it, so their tracking silently does nothing on modern stores. This plugin was built and tested directly against the Checkout Block's own API, as well as the classic checkout, so email capture works correctly either way.

## Features

* Captures the customer's email from the WooCommerce **Checkout Block** the moment it's typed — not just the classic checkout
* Classic checkout is also covered independently, so it works regardless of which checkout your theme uses
* One scheduled recovery email per abandoned cart, sent reliably through Action Scheduler (the same background-job system WooCommerce itself uses)
* Optional one-time discount code (percentage or fixed amount) included automatically in the recovery email
* Automatically detects when a cart is recovered — if the customer completes their order, no recovery email is sent (or a not-yet-sent one is cancelled)
* Admin dashboard of every captured cart: email, contents, total, and status (Pending / Recovered)
* Recovery email is a real WooCommerce transactional email (WooCommerce > Settings > Emails), so it inherits your store's email styling and is fully editable
* HPOS (custom order tables) compatible

## Development

Standard WordPress plugin structure:

```
hdwebmobile-abandoned-cart-recovery.php    Bootstrap
includes/class-hdcart-activator.php
includes/class-hdcart-admin-list-table.php
includes/class-hdcart-admin.php
includes/class-hdcart-capture.php
includes/class-hdcart-core.php
includes/class-hdcart-coupon-manager.php
includes/class-hdcart-hub.php
includes/class-hdcart-order-watcher.php
includes/class-hdcart-recovery-email.php
includes/class-hdcart-repository.php
```

Part of the [HDWebmobile](https://hdwebmobile.com/plugins/) suite of focused, single-purpose WooCommerce plugins.

## License

GPLv2 or later. See [LICENSE](LICENSE).

