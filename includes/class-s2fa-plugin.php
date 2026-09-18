<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class S2FA_Plugin {
    private static $instance = null;
    const META_ENABLED = '_s2fa_enabled';
    const META_SECRET = '_s2fa_secret';
    const META_RECOVERY = '_s2fa_recovery';
    const META_LANG = '_s2fa_lang';
    const COOKIE = 's2fa_pending';

    public static function instance() {
        if ( null === self::$instance ) { self::$instance = new self(); }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
        add_action( 'admin_post_s2fa_save', array( $this, 'save_settings' ) );
        add_action( 'admin_post_s2fa_disable', array( $this, 'disable_2fa' ) );
        add_action( 'admin_post_s2fa_regenerate', array( $this, 'regenerate_codes' ) );
        add_action( 'admin_post_s2fa_language', array( $this, 'save_language' ) );
        add_filter( 'authenticate', array( $this, 'intercept_login' ), 99, 3 );
        add_action( 'login_form_s2fa_verify', array( $this, 'render_verify_screen' ) );
        add_action( 'login_enqueue_scripts', array( $this, 'login_assets' ) );
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'do-marhalei', false, dirname( plugin_basename( S2FA_FILE ) ) . '/languages' );
    }

    public function admin_menu() {
        $fa = 'fa' === $this->preferred_lang();
        add_menu_page(
            $fa ? 'تأیید هویت دو مرحله‌ای' : 'Two-Factor Authentication',
            $fa ? 'دو مرحله‌ای' : 'Two-Factor',
            'read',
            'do-marhalei',
            array( $this, 'settings_page' ),
            'dashicons-shield-alt',
            80
        );
    }

    public function admin_assets( $hook ) {
        if ( 'toplevel_page_do-marhalei' !== $hook ) { return; }
        wp_enqueue_style( 's2fa-admin', S2FA_URL . 'assets/css/admin.css', array(), S2FA_VERSION );
        wp_enqueue_script( 's2fa-qrcode', S2FA_URL . 'assets/vendor/qrcode.min.js', array(), '1.0.0', true );
        wp_enqueue_script( 's2fa-admin', S2FA_URL . 'assets/js/admin.js', array( 's2fa-qrcode' ), S2FA_VERSION, true );
    }

    public function login_assets() {
        if ( isset( $_GET['action'] ) && 's2fa_verify' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
            wp_enqueue_style( 's2fa-login', S2FA_URL . 'assets/css/login.css', array(), S2FA_VERSION );
        }
    }

    private function preferred_lang( $user_id = 0 ) {
        $user_id = $user_id ? absint( $user_id ) : get_current_user_id();
        $lang = $user_id ? (string) get_user_meta( $user_id, self::META_LANG, true ) : '';
        if ( in_array( $lang, array( 'fa', 'en' ), true ) ) { return $lang; }
        return 0 === strpos( determine_locale(), 'fa' ) ? 'fa' : 'en';
    }

    private function apply_user_locale( $user_id = 0 ) {
        $lang = $this->preferred_lang( $user_id );
        switch_to_locale( 'fa' === $lang ? 'fa_IR' : 'en_US' );
        return $lang;
    }

    private function ui( $en, $fa, $lang = '' ) {
        $lang = $lang ? $lang : $this->preferred_lang();
        return 'fa' === $lang ? $fa : $en;
    }

    private function current_data() {
        $user_id = get_current_user_id();
        $secret = (string) get_user_meta( $user_id, self::META_SECRET, true );
        if ( ! $secret ) {
            $secret = S2FA_TOTP::generate_secret();
            update_user_meta( $user_id, self::META_SECRET, $secret );
        }
        return array(
            'enabled' => (bool) get_user_meta( $user_id, self::META_ENABLED, true ),
            'secret' => $secret,
            'recovery' => (array) get_user_meta( $user_id, self::META_RECOVERY, true ),
        );
    }

    public function settings_page() {
        if ( ! current_user_can( 'read' ) ) { wp_die( esc_html__( 'You do not have permission to access this page.', 'do-marhalei' ) ); }
        $user = wp_get_current_user();
        $lang = $this->apply_user_locale( $user->ID );
        $data = $this->current_data();
        $issuer = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
        $uri = S2FA_TOTP::provisioning_uri( $data['secret'], $user->user_email, $issuer );
        $rtl = 'fa' === $lang ? ' s2fa-rtl' : '';
        ?>
        <div class="wrap s2fa-wrap<?php echo esc_attr( $rtl ); ?>">
            <div class="s2fa-shell">
                <header class="s2fa-hero">
                    <div class="s2fa-icon">✦</div>
                    <div>
                        <h1><?php esc_html_e( 'Two-Factor Authentication', 'do-marhalei' ); ?></h1>
                        <p><?php esc_html_e( 'Add an extra verification step to protect your WordPress account.', 'do-marhalei' ); ?></p>
                    </div>
                    <form class="s2fa-language" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="s2fa_language">
                        <?php wp_nonce_field( 's2fa_language', 's2fa_nonce' ); ?>
                        <label for="s2fa-lang"><?php echo esc_html( $this->ui( 'Language', 'زبان', $lang ) ); ?></label>
                        <select id="s2fa-lang" name="s2fa_lang" onchange="this.form.submit()">
                            <option value="fa" <?php selected( $lang, 'fa' ); ?>>فارسی</option>
                            <option value="en" <?php selected( $lang, 'en' ); ?>>English</option>
                        </select>
                    </form>
                    <span class="s2fa-status <?php echo $data['enabled'] ? 'is-on' : 'is-off'; ?>">
                        <?php echo $data['enabled'] ? esc_html__( 'Active', 'do-marhalei' ) : esc_html__( 'Inactive', 'do-marhalei' ); ?>
                    </span>
                </header>

                <?php if ( isset( $_GET['s2fa_notice'] ) ) : ?>
                    <div class="s2fa-notice"><?php echo esc_html( $this->notice_text( sanitize_key( wp_unslash( $_GET['s2fa_notice'] ) ) ) ); ?></div>
                <?php endif; ?>

                <div class="s2fa-grid">
                    <section class="s2fa-card">
                        <div class="s2fa-step"><span>1</span><div><h2><?php esc_html_e( 'Connect authenticator app', 'do-marhalei' ); ?></h2><p><?php esc_html_e( 'Add an account manually in your authenticator app using the setup key below.', 'do-marhalei' ); ?></p></div></div>
                        <div class="s2fa-uri-note"><?php esc_html_e( 'Account', 'do-marhalei' ); ?>: <strong><?php echo esc_html( $user->user_email ); ?></strong><br><?php esc_html_e( 'Issuer', 'do-marhalei' ); ?>: <strong><?php echo esc_html( $issuer ); ?></strong></div>
                        <div class="s2fa-qr-wrap">
                            <div id="s2fa-qrcode" class="s2fa-qrcode" data-uri="<?php echo esc_attr( $uri ); ?>"></div>
                            <div class="s2fa-qr-copy">
                                <strong><?php echo esc_html( $this->ui( 'Scan QR code', 'اسکن QR Code', $lang ) ); ?></strong>
                                <span><?php echo esc_html( $this->ui( 'Scan with Google Authenticator, Microsoft Authenticator, Authy, 1Password or any TOTP app.', 'با Google Authenticator، Microsoft Authenticator، Authy، 1Password یا هر برنامه TOTP اسکن کنید.', $lang ) ); ?></span>
                            </div>
                        </div>
                        <label class="s2fa-label" for="s2fa-secret"><?php esc_html_e( 'Setup key', 'do-marhalei' ); ?></label>
                        <div class="s2fa-secret-row"><code id="s2fa-secret"><?php echo esc_html( $data['secret'] ); ?></code><button type="button" class="button" data-copy="#s2fa-secret"><?php esc_html_e( 'Copy', 'do-marhalei' ); ?></button></div>
                    </section>

                    <section class="s2fa-card">
                        <div class="s2fa-step"><span>2</span><div><h2><?php esc_html_e( 'Verify and activate', 'do-marhalei' ); ?></h2><p><?php esc_html_e( 'Enter the 6-digit code generated by your authenticator app.', 'do-marhalei' ); ?></p></div></div>
                        <?php if ( ! $data['enabled'] ) : ?>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                <input type="hidden" name="action" value="s2fa_save">
                                <?php wp_nonce_field( 's2fa_save', 's2fa_nonce' ); ?>
                                <input class="s2fa-code" type="text" name="s2fa_code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required>
                                <button class="button button-primary s2fa-primary" type="submit"><?php esc_html_e( 'Activate two-factor authentication', 'do-marhalei' ); ?></button>
                            </form>
                        <?php else : ?>
                            <div class="s2fa-success"><strong>✓ <?php esc_html_e( 'Protection is enabled', 'do-marhalei' ); ?></strong><p><?php esc_html_e( 'Your account now requires a second verification step after your password.', 'do-marhalei' ); ?></p></div>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Disable two-factor authentication?', 'do-marhalei' ) ); ?>');">
                                <input type="hidden" name="action" value="s2fa_disable">
                                <?php wp_nonce_field( 's2fa_disable', 's2fa_nonce' ); ?>
                                <button class="button s2fa-danger" type="submit"><?php esc_html_e( 'Disable 2FA', 'do-marhalei' ); ?></button>
                            </form>
                        <?php endif; ?>
                    </section>

                    <?php if ( $data['enabled'] ) : ?>
                    <section class="s2fa-card s2fa-wide">
                        <div class="s2fa-step"><span>3</span><div><h2><?php esc_html_e( 'Recovery codes', 'do-marhalei' ); ?></h2><p><?php esc_html_e( 'Store these codes somewhere safe. Each code can be used once.', 'do-marhalei' ); ?></p></div></div>
                        <div class="s2fa-recovery-grid">
                            <?php foreach ( $data['recovery'] as $item ) : if ( ! empty( $item['used'] ) ) { continue; } ?>
                                <code><?php echo esc_html( $item['code'] ); ?></code>
                            <?php endforeach; ?>
                        </div>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="s2fa_regenerate">
                            <?php wp_nonce_field( 's2fa_regenerate', 's2fa_nonce' ); ?>
                            <button class="button" type="submit"><?php esc_html_e( 'Generate new recovery codes', 'do-marhalei' ); ?></button>
                        </form>
                    </section>
                    <?php endif; ?>
                </div>

                <section class="s2fa-card s2fa-about">
                    <div class="s2fa-about-icon">SR</div>
                    <div class="s2fa-about-content">
                        <span class="s2fa-kicker"><?php esc_html_e( 'About the plugin', 'do-marhalei' ); ?></span>
                        <h2><?php esc_html_e( 'Two-Factor Authentication', 'do-marhalei' ); ?></h2>
                        <p><?php esc_html_e( 'A lightweight security plugin for WordPress that adds a second verification step using time-based one-time passwords (TOTP) and recovery codes. It works locally without requiring an SMS service or an external authentication account.', 'do-marhalei' ); ?></p>
                        <div class="s2fa-author-meta">
                            <div><span><?php esc_html_e( 'Developer', 'do-marhalei' ); ?></span><strong><?php esc_html_e( 'Sahand Rezvan', 'do-marhalei' ); ?> <small>— سهند رضوان</small></strong></div>
                            <a class="button s2fa-telegram" href="https://t.me/sahandse" target="_blank" rel="noopener noreferrer">t.me/sahandse ↗</a>
                        </div>
                    </div>
                </section>

                <footer class="s2fa-foot"><?php esc_html_e( 'TOTP works offline and is compatible with common authenticator apps.', 'do-marhalei' ); ?></footer>
            </div>
        </div>
        <?php
    }

    private function notice_text( $key ) {
        $map = array(
            'enabled' => __( 'Two-factor authentication has been enabled.', 'do-marhalei' ),
            'invalid' => __( 'The verification code was invalid. Please try again.', 'do-marhalei' ),
            'disabled' => __( 'Two-factor authentication has been disabled.', 'do-marhalei' ),
            'regenerated' => __( 'New recovery codes have been generated.', 'do-marhalei' ),
        );
        return isset( $map[$key] ) ? $map[$key] : '';
    }

    public function save_language() {
        if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) { wp_die( 'Unauthorized request.' ); }
        check_admin_referer( 's2fa_language', 's2fa_nonce' );
        $lang = isset( $_POST['s2fa_lang'] ) ? sanitize_key( wp_unslash( $_POST['s2fa_lang'] ) ) : 'en';
        if ( ! in_array( $lang, array( 'fa', 'en' ), true ) ) { $lang = 'en'; }
        update_user_meta( get_current_user_id(), self::META_LANG, $lang );
        wp_safe_redirect( add_query_arg( 'page', 'do-marhalei', admin_url( 'admin.php' ) ) );
        exit;
    }

    public function save_settings() {
        if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) { wp_die( esc_html__( 'Unauthorized request.', 'do-marhalei' ) ); }
        check_admin_referer( 's2fa_save', 's2fa_nonce' );
        $code = isset( $_POST['s2fa_code'] ) ? sanitize_text_field( wp_unslash( $_POST['s2fa_code'] ) ) : '';
        $secret = (string) get_user_meta( get_current_user_id(), self::META_SECRET, true );
        if ( $secret && S2FA_TOTP::verify( $secret, $code ) ) {
            update_user_meta( get_current_user_id(), self::META_ENABLED, 1 );
            update_user_meta( get_current_user_id(), self::META_RECOVERY, $this->new_recovery_codes() );
            $this->redirect_notice( 'enabled' );
        }
        $this->redirect_notice( 'invalid' );
    }

    public function disable_2fa() {
        if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) { wp_die( esc_html__( 'Unauthorized request.', 'do-marhalei' ) ); }
        check_admin_referer( 's2fa_disable', 's2fa_nonce' );
        delete_user_meta( get_current_user_id(), self::META_ENABLED );
        delete_user_meta( get_current_user_id(), self::META_SECRET );
        delete_user_meta( get_current_user_id(), self::META_RECOVERY );
        $this->redirect_notice( 'disabled' );
    }

    public function regenerate_codes() {
        if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) { wp_die( esc_html__( 'Unauthorized request.', 'do-marhalei' ) ); }
        check_admin_referer( 's2fa_regenerate', 's2fa_nonce' );
        update_user_meta( get_current_user_id(), self::META_RECOVERY, $this->new_recovery_codes() );
        $this->redirect_notice( 'regenerated' );
    }

    private function redirect_notice( $notice ) {
        wp_safe_redirect( add_query_arg( array( 'page' => 'do-marhalei', 's2fa_notice' => sanitize_key( $notice ) ), admin_url( 'admin.php' ) ) );
        exit;
    }

    private function new_recovery_codes() {
        $codes = array();
        for ( $i = 0; $i < 8; $i++ ) {
            $raw = strtoupper( wp_generate_password( 10, false, false ) );
            $code = substr( $raw, 0, 5 ) . '-' . substr( $raw, 5, 5 );
            $codes[] = array( 'code' => $code, 'hash' => wp_hash_password( $code ), 'used' => false );
        }
        return $codes;
    }

    public function intercept_login( $user, $username, $password ) {
        if ( is_wp_error( $user ) || ! $user instanceof WP_User ) { return $user; }
        if ( empty( $password ) || ! get_user_meta( $user->ID, self::META_ENABLED, true ) ) { return $user; }
        if ( isset( $_POST['s2fa_verified_token'] ) ) { return $user; }

        $token = wp_generate_password( 48, false, false );
        set_transient( 's2fa_' . hash( 'sha256', $token ), array( 'user_id' => $user->ID, 'redirect_to' => isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : admin_url() ), 5 * MINUTE_IN_SECONDS );
        $secure = is_ssl();
        setcookie( self::COOKIE, $token, time() + 300, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, true );
        wp_safe_redirect( add_query_arg( 'action', 's2fa_verify', wp_login_url() ) );
        exit;
    }

    public function render_verify_screen() {
        $token = isset( $_COOKIE[ self::COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) : '';
        $key = $token ? 's2fa_' . hash( 'sha256', $token ) : '';
        $pending = $key ? get_transient( $key ) : false;
        $error = '';
        if ( $pending && ! empty( $pending['user_id'] ) ) {
            $this->apply_user_locale( absint( $pending['user_id'] ) );
        }

        if ( ! $pending || empty( $pending['user_id'] ) ) {
            $error = __( 'This verification session has expired. Please sign in again.', 'do-marhalei' );
        } elseif ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
            if ( ! isset( $_POST['s2fa_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['s2fa_login_nonce'] ) ), 's2fa_login_verify' ) ) {
                $error = __( 'Security check failed. Please try again.', 'do-marhalei' );
            } else {
                $code = isset( $_POST['s2fa_code'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['s2fa_code'] ) ) ) : '';
                $user_id = absint( $pending['user_id'] );
                $secret = (string) get_user_meta( $user_id, self::META_SECRET, true );
                $valid = S2FA_TOTP::verify( $secret, $code );
                if ( ! $valid ) { $valid = $this->use_recovery_code( $user_id, $code ); }
                if ( $valid ) {
                    delete_transient( $key );
                    setcookie( self::COOKIE, '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
                    wp_set_current_user( $user_id );
                    wp_set_auth_cookie( $user_id, ! empty( $_POST['rememberme'] ), is_ssl() );
                    do_action( 'wp_login', get_userdata( $user_id )->user_login, get_userdata( $user_id ) );
                    wp_safe_redirect( ! empty( $pending['redirect_to'] ) ? $pending['redirect_to'] : admin_url() );
                    exit;
                }
                $error = __( 'Invalid authentication or recovery code.', 'do-marhalei' );
            }
        }

        login_header( __( 'Two-Factor Verification', 'do-marhalei' ), '', $error ? new WP_Error( 's2fa_error', $error ) : null );
        ?>
        <form name="s2faform" id="s2faform" action="<?php echo esc_url( add_query_arg( 'action', 's2fa_verify', wp_login_url() ) ); ?>" method="post">
            <div class="s2fa-login-head"><span class="s2fa-login-icon">✦</span><h2><?php esc_html_e( 'Verify your identity', 'do-marhalei' ); ?></h2><p><?php esc_html_e( 'Enter the 6-digit code from your authenticator app, or use a recovery code.', 'do-marhalei' ); ?></p></div>
            <p><label for="s2fa_code"><?php esc_html_e( 'Authentication code', 'do-marhalei' ); ?><br><input type="text" name="s2fa_code" id="s2fa_code" class="input s2fa-login-code" inputmode="text" autocomplete="one-time-code" autofocus required></label></p>
            <?php wp_nonce_field( 's2fa_login_verify', 's2fa_login_nonce' ); ?>
            <p class="submit"><input type="submit" class="button button-primary button-large" value="<?php echo esc_attr__( 'Verify and continue', 'do-marhalei' ); ?>"></p>
            <p class="s2fa-login-help"><a href="<?php echo esc_url( wp_login_url() ); ?>">← <?php esc_html_e( 'Back to sign in', 'do-marhalei' ); ?></a></p>
        </form>
        <?php
        login_footer();
        exit;
    }

    private function use_recovery_code( $user_id, $submitted ) {
        $submitted = strtoupper( trim( $submitted ) );
        $items = (array) get_user_meta( $user_id, self::META_RECOVERY, true );
        foreach ( $items as $index => $item ) {
            if ( ! empty( $item['used'] ) || empty( $item['hash'] ) ) { continue; }
            if ( wp_check_password( $submitted, $item['hash'] ) ) {
                $items[$index]['used'] = true;
                $items[$index]['code'] = '';
                update_user_meta( $user_id, self::META_RECOVERY, $items );
                return true;
            }
        }
        return false;
    }
}
