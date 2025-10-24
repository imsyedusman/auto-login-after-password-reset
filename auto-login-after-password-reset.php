<?php
/**
 * Plugin Name:       Auto Login After Password Reset (Woo Compatible)
 * Plugin URI:        https://wordpress.org/plugins/auto-login-after-password-reset/
 * Description:       Logs users in immediately after a successful password reset/change and redirects them to a configurable page. Works with WooCommerce and default WordPress flows.
 * Version:           1.0.0
 * Author:            Syed Usman
 * Author URI:        https://profiles.wordpress.org/imsyedusman/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       auto-login-after-password-reset
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WALR_Auto_Login_After_Reset' ) ) :

final class WALR_Auto_Login_After_Reset {

	const VERSION    = '1.0.0';
	const OPTION_KEY = 'walr_settings';

	/** @var self|null */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Note: Since WP 4.6, .org auto-loads translations. No load_plugin_textdomain() call needed.

		// Load admin settings.
		$this->maybe_include_admin();

		// Core WP password reset hook.
		add_action( 'password_reset', [ $this, 'handle_wp_password_reset' ], 10, 2 );

		// Intercept the interim wp-login redirect after reset.
		add_action( 'login_init', [ $this, 'maybe_redirect_after_core_reset' ] );

		// WooCommerce-specific reset hook.
		add_action( 'woocommerce_customer_reset_password', [ $this, 'handle_wc_password_reset' ], 10, 1 );

		// Password change while already logged in.
		add_action( 'after_password_reset', [ $this, 'handle_after_password_reset' ], 10, 2 );
	}

	/**
	 * Include admin settings class when in admin.
	 */
	private function maybe_include_admin() {
		if ( is_admin() ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-walr-settings.php';

			// Boot settings page.
			WALR_Settings::instance(
				self::OPTION_KEY,
				[ 'enabled' => 1, 'redirect_url' => '' ],
				__( 'Auto Login After Reset', 'auto-login-after-password-reset' )
			);
		}
	}

	/**
	 * Get merged settings (with defaults).
	 *
	 * @return array
	 */
	private function get_settings() {
		$defaults = [ 'enabled' => 1, 'redirect_url' => '' ];
		$opts     = get_option( self::OPTION_KEY, [] );
		return wp_parse_args( is_array( $opts ) ? $opts : [], $defaults );
	}

	/**
	 * Are we enabled?
	 *
	 * @return bool
	 */
	private function is_enabled() {
		$settings = $this->get_settings();
		return ! empty( $settings['enabled'] );
	}

	/**
	 * Return the effective redirect URL.
	 *
	 * @return string
	 */
	private function get_redirect_url() {
		$settings = $this->get_settings();

		// Admin-configured URL takes precedence if valid.
		if ( ! empty( $settings['redirect_url'] ) ) {
			$url = esc_url_raw( $settings['redirect_url'] );
			if ( ! empty( $url ) ) {
				return $this->ensure_safe_redirect( $url );
			}
		}

		// If WooCommerce is active and My Account page exists, use it.
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$myaccount = wc_get_page_permalink( 'myaccount' );
			if ( $myaccount ) {
				return $this->ensure_safe_redirect( $myaccount );
			}
		}

		// Fallback for non-Woo sites: user profile if available, else home.
		if ( is_user_logged_in() ) {
			return $this->ensure_safe_redirect( admin_url( 'profile.php' ) );
		}

		return $this->ensure_safe_redirect( home_url( '/' ) );
	}

	/**
	 * Ensure redirect is safe and same-host.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function ensure_safe_redirect( $url ) {
		$validated = wp_validate_redirect( $url, home_url( '/' ) );

		$host   = wp_parse_url( home_url(), PHP_URL_HOST );
		$target = wp_parse_url( $validated, PHP_URL_HOST );

		if ( $host && $target && $host !== $target ) {
			return home_url( '/' );
		}
		return $validated;
	}

	/**
	 * Log user in safely and set auth cookie.
	 *
	 * @param WP_User $user User object.
	 * @return void
	 */
	private function login_user( $user ) {
		if ( ! ( $user instanceof WP_User ) || empty( $user->ID ) || is_wp_error( $user ) ) {
			return;
		}

		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, false, is_ssl() );

		/**
		 * Mirror core login behavior for compatibility with other plugins.
		 *
		 * @param string  $user_login User login name.
		 * @param WP_User $user       User object.
		 */
		do_action( 'wp_login', $user->user_login, $user );
	}

	/**
	 * Handle core WordPress password reset (via wp-login.php).
	 *
	 * @param WP_User $user     User.
	 * @param string  $new_pass New password.
	 * @return void
	 */
	public function handle_wp_password_reset( $user, $new_pass ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.UnusedVariable
		if ( ! $this->is_enabled() ) {
			return;
		}

		$this->login_user( $user );

		// Mark this request so login_init can redirect cleanly.
		set_transient( 'walr_recent_reset_' . $user->ID, 1, MINUTE_IN_SECONDS * 10 );
	}

	/**
	 * If we just reset, redirect from wp-login landing to our target.
	 *
	 * @return void
	 */
	public function maybe_redirect_after_core_reset() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! $user || ! $user->ID ) {
			return;
		}

		$flag = get_transient( 'walr_recent_reset_' . $user->ID );
		if ( $flag ) {
			delete_transient( 'walr_recent_reset_' . $user->ID );
			wp_safe_redirect( $this->get_redirect_url() );
			exit;
		}
	}

	/**
	 * Handle WooCommerce reset flows.
	 *
	 * @param WP_User $user User.
	 * @return void
	 */
	public function handle_wc_password_reset( $user ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( $user instanceof WP_User ) {
			$this->login_user( $user );
			wp_safe_redirect( $this->get_redirect_url() );
			exit;
		}
	}

	/**
	 * Handle password changes triggered while logged in.
	 *
	 * @param WP_User $user     User.
	 * @param string  $new_pass New password.
	 * @return void
	 */
	public function handle_after_password_reset( $user, $new_pass ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.UnusedVariable
		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( is_user_logged_in() ) {
			wp_safe_redirect( $this->get_redirect_url() );
			exit;
		}
	}
}

// Bootstrap.
function walr_bootstrap() {
	return WALR_Auto_Login_After_Reset::instance();
}
add_action( 'plugins_loaded', 'walr_bootstrap' );

endif;
