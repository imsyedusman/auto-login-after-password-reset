<?php
/**
 * Admin Settings for Auto Login After Password Reset.
 *
 * @package AutoLoginAfterPasswordReset
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WALR_Settings' ) ) :

class WALR_Settings {

	/** @var self|null */
	private static $instance = null;

	/** @var string */
	private $option_key;

	/** @var array */
	private $defaults;

	/** @var string */
	private $page_title;

	/**
	 * Singleton accessor.
	 *
	 * @param string $option_key Option key for stored settings.
	 * @param array  $defaults   Default settings array.
	 * @param string $page_title Menu/page title.
	 * @return self
	 */
	public static function instance( $option_key = 'walr_settings', $defaults = [], $page_title = '' ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $option_key, $defaults, $page_title );
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @param string $option_key Option key.
	 * @param array  $defaults   Defaults.
	 * @param string $page_title Page title.
	 */
	private function __construct( $option_key, $defaults, $page_title ) {
		$this->option_key = $option_key;
		$this->defaults   = wp_parse_args( (array) $defaults, [ 'enabled' => 1, 'redirect_url' => '' ] );
		$this->page_title = $page_title ? $page_title : __( 'Auto Login After Reset', 'auto-login-after-password-reset' );

		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
	}

	/**
	 * Register settings & fields via Settings API.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'walr_settings_group',
			$this->option_key,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => $this->defaults,
				'show_in_rest'      => false,
			]
		);

		add_settings_section(
			'walr_main_section',
			esc_html__( 'Auto Login After Password Reset', 'auto-login-after-password-reset' ),
			[ $this, 'section_intro' ],
			'walr_settings_page'
		);

		add_settings_field(
			'walr_enabled',
			esc_html__( 'Enable auto login', 'auto-login-after-password-reset' ),
			[ $this, 'field_enabled_cb' ],
			'walr_settings_page',
			'walr_main_section'
		);

		add_settings_field(
			'walr_redirect_url',
			esc_html__( 'Custom redirect URL (optional)', 'auto-login-after-password-reset' ),
			[ $this, 'field_redirect_cb' ],
			'walr_settings_page',
			'walr_main_section'
		);
	}

	/**
	 * Section intro callback.
	 */
	public function section_intro() {
		echo '<p>' . esc_html__( 'Automatically log users in after a successful password reset/change and redirect them to your chosen page.', 'auto-login-after-password-reset' ) . '</p>';
	}

	/**
	 * Enabled checkbox field.
	 */
	public function field_enabled_cb() {
		$settings = $this->get_settings();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_key ); ?>[enabled]" value="1" <?php checked( $settings['enabled'], 1 ); ?> />
			<?php esc_html_e( 'Automatically log the user in after password reset/change', 'auto-login-after-password-reset' ); ?>
		</label>
		<?php
	}

	/**
	 * Redirect URL field.
	 */
	public function field_redirect_cb() {
		$settings = $this->get_settings();
		?>
		<input type="url"
		       class="regular-text"
		       name="<?php echo esc_attr( $this->option_key ); ?>[redirect_url]"
		       value="<?php echo esc_attr( $settings['redirect_url'] ); ?>"
		       placeholder="<?php echo esc_attr( home_url( '/my-account/' ) ); ?>" />
		<p class="description">
			<?php esc_html_e( 'Optional. Leave empty to use WooCommerce My Account (if active) or your profile page. Must be on the same domain.', 'auto-login-after-password-reset' ); ?>
		</p>
		<?php
	}

	/**
	 * Add settings page under Settings.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			esc_html__( 'Auto Login After Reset', 'auto-login-after-password-reset' ),
			esc_html__( 'Auto Login After Reset', 'auto-login-after-password-reset' ),
			'manage_options',
			'walr-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $this->page_title ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'walr_settings_group' ); // Nonce, option group.
				do_settings_sections( 'walr_settings_page' );
				submit_button( esc_html__( 'Save Changes', 'auto-login-after-password-reset' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Get merged settings.
	 *
	 * @return array
	 */
	private function get_settings() {
		$opts = get_option( $this->option_key, [] );
		return wp_parse_args( is_array( $opts ) ? $opts : [], $this->defaults );
	}

	/**
	 * Sanitize settings prior to save.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$output = $this->defaults;

		if ( is_array( $input ) ) {
			$output['enabled'] = empty( $input['enabled'] ) ? 0 : 1;

			if ( ! empty( $input['redirect_url'] ) ) {
				$maybe = esc_url_raw( trim( (string) $input['redirect_url'] ) );

				// Only accept absolute same-host URLs.
				$site_host  = wp_parse_url( home_url(), PHP_URL_HOST );
				$input_host = $maybe ? wp_parse_url( $maybe, PHP_URL_HOST ) : '';

				if ( $maybe && $site_host && $input_host && $site_host === $input_host ) {
					$output['redirect_url'] = $maybe;
				} else {
					add_settings_error(
						$this->option_key,
						'walr_bad_url',
						esc_html__( 'The redirect URL must be a full URL on the same domain.', 'auto-login-after-password-reset' ),
						'error'
					);
				}
			} else {
				$output['redirect_url'] = '';
			}
		}

		return $output;
	}
}

endif;
