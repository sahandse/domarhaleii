<?php
/**
 * Plugin Name: Two-Step Authentication
 * Description: Secure bilingual two-factor authentication for WordPress using TOTP and recovery codes, with a minimal responsive admin interface.
 * Version:     1.1.2
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author:      Sahand Rezvan
 * Author URI:  https://t.me/sahandse
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: do-marhalei
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'S2FA_VERSION', '1.1.2' );
define( 'S2FA_FILE', __FILE__ );
define( 'S2FA_DIR', plugin_dir_path( __FILE__ ) );
define( 'S2FA_URL', plugin_dir_url( __FILE__ ) );

require_once S2FA_DIR . 'includes/class-s2fa-totp.php';
require_once S2FA_DIR . 'includes/class-s2fa-plugin.php';

S2FA_Plugin::instance();
