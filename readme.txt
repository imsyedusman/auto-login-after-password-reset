=== Auto Login After Password Reset (Woo Compatible) ===
Contributors: imsyedusman
Tags: login, password reset, authentication, woocommerce, user experience
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Logs users in right after password reset/change and redirects to a page you choose. Works with WooCommerce and default WordPress flows.


== Description ==

**Woo Auto Login After Password Reset** improves the user experience by logging users in right after a successful password reset/change and taking them directly to a useful destination (e.g. WooCommerce **My Account**). No more “reset → go to login screen → log in again” friction.

**Features:**
- Auto-login users right after password reset/change
- Compatible with both **WooCommerce** and **default WordPress** reset flows
- Configurable **redirect URL** (falls back to WooCommerce “My Account” if active)
- Toggle auto-login behavior in **Settings → Auto Login After Reset**
- Clean, secure, and translation-ready

**Security & Compliance**
- Uses WordPress pluggable auth functions (`wp_set_auth_cookie`, `wp_set_current_user`)
- Redirects are validated and restricted to the same host
- Follows WordPress Coding Standards and .org guidelines
- Fully GPLv2 (or later)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/woo-auto-login-after-password-reset/`, or install via the Plugins screen in WordPress.
2. Activate the plugin through the **Plugins** screen.
3. Go to **Settings → Auto Login After Reset** to enable/disable and set an optional redirect URL.

== Frequently Asked Questions ==

= Does this require WooCommerce? =
No. If WooCommerce is active, we’ll default to redirecting to **My Account**. Otherwise we fall back to the user profile page or your specified custom URL.

= Will this work with the default WordPress reset form on wp-login.php? =
Yes. After a successful reset, the plugin sets the auth cookie and seamlessly redirects the user to your configured destination.

= Is the redirect URL safe? =
Yes. The plugin validates and restricts redirects to the same domain to prevent open redirect issues.

= Can I disable auto login without deactivating the plugin? =
Yes. Use the checkbox in **Settings → Auto Login After Reset**.

== Screenshots ==
1. Settings screen under **Settings → Auto Login After Reset**

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.

