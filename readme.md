# Auto Login After Password Reset (Woo Compatible)

**Automatically log users in right after they reset their password — for both WordPress and WooCommerce.**

This lightweight plugin enhances user experience by skipping the extra “log in again” step after a password reset. Once users successfully reset or change their password, they’re logged in instantly and redirected to a configurable page (like **My Account**).

<img width="3294" height="2430" alt="image" src="https://github.com/user-attachments/assets/5cec6fd5-39b3-46b6-ab27-877587bf37c9" />


---

## ✨ Features

- 🔒 **Instant login** after password reset or change  
- ⚙️ Works with both **core WordPress** and **WooCommerce** reset flows  
- 🔁 Optional **custom redirect URL**  
- ✅ Built using **WordPress Coding Standards**  
- 🌐 Fully translation-ready (`Text Domain: auto-login-after-password-reset`)  
- 🧩 100% GPLv2 or later

---

## 🧠 How It Works

The plugin hooks into:
- `password_reset` → for default WordPress reset flow  
- `woocommerce_customer_reset_password` → for WooCommerce reset flow  
- `after_password_reset` → for password changes while logged in  

It then uses WordPress’ built-in authentication functions:
```php
wp_set_current_user();
wp_set_auth_cookie();
```

## ⚙️ Installation

Upload the plugin to /wp-content/plugins/auto-login-after-password-reset/

Activate it via Plugins → Installed Plugins

Go to Settings → Auto Login After Reset

Toggle the feature on/off and set a custom redirect URL (optional)
