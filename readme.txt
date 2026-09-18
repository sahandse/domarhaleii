=== Two-Step Authentication ===
Contributors: sahand
Tags: two factor, 2fa, totp, security, authenticator
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Minimal bilingual TOTP two-factor authentication for WordPress with a modern, responsive admin interface.

== Description ==
Two-Step Authentication adds a second authentication step to WordPress login using standard TOTP authenticator applications. It is designed to be lightweight, private and simple to configure from the WordPress admin area. TOTP verification runs locally and does not require an SMS gateway or external authentication account.

Developer: Sahand Rezvan (سهند رضوان)
Telegram: https://t.me/sahandse

Features:
* TOTP compatible with common authenticator apps.
* Manual setup key compatible with standard TOTP authenticator apps.
* One-time recovery codes.
* Per-user activation.
* RTL-friendly responsive admin interface.
* Translation-ready with WordPress gettext APIs.
* No cloud account, SMS service, or external authentication API required.

== Installation ==
1. Upload the plugin ZIP in Plugins > Add New > Upload Plugin.
2. Activate the plugin.
3. Open Two-Factor from the WordPress admin menu.
4. Scan the QR code, enter a generated 6-digit code, and activate protection.
5. Save your recovery codes in a secure place.

== Frequently Asked Questions ==
= Which authenticator apps work? =
Any standard TOTP application should work, including Google Authenticator, Microsoft Authenticator, Authy, 1Password, Bitwarden and similar apps.

= Does this plugin send data to an external service? =
No. TOTP verification is performed locally on your WordPress installation.

== Changelog ==
= 1.0.3 =
* Changed the official plugin name to Two-Step Authentication for WordPress.org compatibility.
* Kept the Persian name in the localized interface.

= 1.0.2 =
* Renamed the plugin to دو مرحله‌ای.
* Removed the developer name from the product title while keeping developer attribution in the About section.

= 1.0.1 =
* Added an About section to the plugin settings.
* Added developer information: Sahand Rezvan / سهند رضوان.
* Added Telegram contact link: t.me/sahandse.
* Improved plugin description and metadata.

= 1.0.0 =
* Initial release.
