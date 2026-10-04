=== Sahand Two-Step Authentication ===
Contributors: sahandse
Tags: two factor, 2fa, totp, security, authenticator
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight bilingual TOTP two-factor authentication with recovery codes and a responsive admin interface.

== Description ==
Sahand Two-Step Authentication adds a second authentication step to the login flow using standard TOTP authenticator applications. It is designed to be lightweight, private, and simple to configure from the admin area. TOTP verification runs locally and does not require an SMS gateway or an external authentication account.

Developer: Sahand Rezvan (سهند رضوان)
Telegram: https://t.me/sahandse

Features:
* Offline QR code setup generated locally in the browser.
* Built-in Persian / English language selector saved per user.
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
3. Open the plugin settings from the admin menu.
4. Scan the QR code, enter a generated 6-digit code, and activate protection.
5. Save your recovery codes in a secure place.

== Frequently Asked Questions ==
= Which authenticator apps work? =
Any standard TOTP application should work, including common authenticator and password-manager applications that support TOTP.

= Does this plugin send data to an external service? =
No. TOTP verification is performed locally on your installation.

== Changelog ==
= 1.1.3 =
* Updated the plugin name and text domain for WordPress.org directory review.
* Corrected the WordPress.org contributor username and stable tag.
* Removed bundled translation files so translations can be managed through translate.wordpress.org.

= 1.1.0 =
* Added local QR code generation for TOTP setup without sending the secret to external services.
* Added a Persian / English language selector in the admin panel.
* Saved language preference separately for each user.
* Applied the preferred language to the second-step login screen.

= 1.0.3 =
* Updated the public plugin name for directory compatibility.
* Kept the Persian name in the localized interface.

= 1.0.2 =
* Added the Persian localized product name.
* Kept developer attribution in the About section.

= 1.0.1 =
* Added an About section to the plugin settings.
* Added developer information: Sahand Rezvan / سهند رضوان.
* Added Telegram contact link: t.me/sahandse.
* Improved plugin description and metadata.

= 1.0.0 =
* Initial release.
