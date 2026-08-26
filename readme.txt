=== HDWebmobile Abandoned Cart Recovery ===
Contributors: htrxuan
Donate link: https://paypal.me/htrxuan/20
Tags: woocommerce, abandoned cart, cart recovery, checkout, email marketing
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.2
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Recovers abandoned WooCommerce carts with a scheduled email, correctly capturing the customer's email from both the Blocks and classic checkout.

== Description ==

HDWebmobile Abandoned Cart Recovery watches your checkout and captures a visitor's email address the moment they type it in — before they ever place an order. If they leave without completing checkout, a recovery email goes out automatically after a delay you choose, optionally including a one-time discount code.

Many existing cart-recovery plugins were built before the WooCommerce Checkout Block existed and simply never learned to read from it, so their tracking silently does nothing on modern stores. This plugin was built and tested directly against the Checkout Block's own API, as well as the classic checkout, so email capture works correctly either way.

= Key Features =
* Captures the customer's email from the WooCommerce **Checkout Block** the moment it's typed — not just the classic checkout
* Classic checkout is also covered independently, so it works regardless of which checkout your theme uses
* One scheduled recovery email per abandoned cart, sent reliably through Action Scheduler (the same background-job system WooCommerce itself uses)
* Optional one-time discount code (percentage or fixed amount) included automatically in the recovery email
* Automatically detects when a cart is recovered — if the customer completes their order, no recovery email is sent (or a not-yet-sent one is cancelled)
* Admin dashboard of every captured cart: email, contents, total, and status (Pending / Recovered)
* Recovery email is a real WooCommerce transactional email (WooCommerce > Settings > Emails), so it inherits your store's email styling and is fully editable
* HPOS (custom order tables) compatible

= Limitations (please read before installing) =
* One scheduled recovery email per abandoned cart — not a multi-step drip sequence
* A cart only starts being tracked once a valid email has been entered — fully anonymous browsing is never recorded
* Recovery is detected by matching the completed order's email against a pending cart captured earlier; it doesn't re-verify the order contains the exact same items
* Email only — no SMS or push notifications in this version
* No email marketing platform integration (Mailchimp, Klaviyo, etc.) in this version
* If a visitor's browser blocks the session cookie WooCommerce itself needs for cart/checkout to function, no plugin — including this one — can track that visitor; this is a platform limitation, not something this plugin claims to work around

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/hdwebmobile-abandoned-cart-recovery` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress. WooCommerce must already be installed and active.
3. Configure the recovery delay and optional discount under WooCommerce > Cart Recovery.

== How to Use ==

= 1. Turn it on and set your timing =
Go to **WooCommerce > Cart Recovery** and tick **Enable Cart Recovery**. Set **Send recovery email after (hours)** — this is how long to wait after a visitor enters their email before sending the reminder, giving them a chance to complete checkout normally first.

= 2. Optionally add a discount incentive =
Tick **Include a discount code** to have every recovery email carry a one-time coupon. Choose **Percent off** or **Fixed amount off**, set the amount, and set how many days the code stays valid after being generated.

= 3. Nothing else to configure =
As soon as it's enabled, the plugin silently watches your existing checkout — both the Checkout Block and the classic checkout — for an email address. There's no shortcode, widget, or frontend setting to add anywhere.

= 4. What happens when someone abandons their cart =
The moment a visitor types a valid email at checkout, their cart contents and total are recorded. If they don't complete an order within your configured delay, a recovery email is sent automatically with their cart contents, total, a link back to checkout, and their discount code if you enabled one.

= 5. What happens when someone completes their order =
If the same email places a real order — before or after the recovery email goes out — that cart is automatically marked **Recovered** and linked to the resulting order. If the recovery email hadn't been sent yet, it's cancelled and never goes out.

= 6. Reviewing captured carts =
Go to **WooCommerce > Abandoned Carts** to see every captured cart: email, item count and total, status, and timestamps. Filter by status or search by email.

= 7. Customizing the email =
The recovery email is a normal WooCommerce email under **WooCommerce > Settings > Emails > "Abandoned Cart Recovery"** — its subject, heading, and enabled/disabled state are all editable there like any other WooCommerce email.

== Screenshots ==

1. Cart Recovery settings — enable the plugin, set the delay, and configure an optional discount incentive.
2. The Abandoned Carts admin screen, showing captured carts and their recovery status.
3. The recovery email as received by a customer, including their cart contents and discount code.

== Changelog ==

= 1.0.2 =
* Confirmed compatibility with WordPress 7.1.
* Renamed the internal hub-coordination class to a plugin-specific name for WordPress.org naming-convention compliance. No functional changes.

= 1.0.1 =
* The Abandoned Carts and Cart Recovery admin screens now live under WooCommerce > HDWebmobile as tabs, alongside every other HDWebmobile plugin you have active, instead of their own separate WooCommerce submenu items. No functional changes to cart-recovery behavior.

= 1.0.0 =
* Initial release: dual Blocks/classic checkout email capture, Action Scheduler-driven recovery email, optional discount coupon, automatic recovery detection, admin cart dashboard, HPOS compatibility declared.
