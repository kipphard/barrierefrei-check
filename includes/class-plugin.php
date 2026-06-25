<?php
/**
 * Plugin bootstrap: wires hooks, the admin UI, and the statement shortcode.
 *
 * @package Kipphard\Barrierefrei
 */

namespace Kipphard\Barrierefrei;

defined( 'ABSPATH' ) || exit;

/**
 * Singleton entry point.
 */
final class Plugin {

	/** @var Plugin|null */
	private static $instance = null;

	/**
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor (singleton).
	 */
	private function __construct() {}

	/**
	 * Activation: seed default options.
	 */
	public static function activate() {
		if ( false === get_option( Helpers::OPT_STATEMENT, false ) ) {
			add_option(
				Helpers::OPT_STATEMENT,
				array(
					'org_name'         => get_bloginfo( 'name' ),
					'conformance'      => 'partial',
					'contact_email'    => get_bloginfo( 'admin_email' ),
					'enforcement_body' => '',
				)
			);
		}
	}

	/**
	 * Deactivation: nothing persistent to tear down yet.
	 */
	public static function deactivate() {}

	/**
	 * Register runtime hooks.
	 */
	public function boot() {
		load_plugin_textdomain(
			'barrierefrei-check',
			false,
			dirname( plugin_basename( BFC_FILE ) ) . '/languages'
		);

		add_shortcode( 'barrierefreiheitserklaerung', array( Statement::class, 'shortcode' ) );

		if ( is_admin() ) {
			( new Admin() )->hooks();
		}
	}
}
