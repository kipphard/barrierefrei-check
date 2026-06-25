<?php
/**
 * Plugin Name:       Barrierefrei – BFSG & WCAG Accessibility Check
 * Plugin URI:        https://products.kipphard.com/barrierefrei-check
 * Description:        Prüft deine Website auf Barrierefreiheit (WCAG 2.2 / BFSG), zeigt priorisierte Fehler mit Lösungshinweisen und erzeugt eine rechtssichere Barrierefreiheitserklärung. Ehrliches Audit-Tool – kein Overlay.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            André Kipphard
 * Author URI:        https://kipphard.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       barrierefrei-check
 * Domain Path:       /languages
 *
 * @package Kipphard\Barrierefrei
 */

defined( 'ABSPATH' ) || exit;

define( 'BFC_VERSION', '0.1.0' );
define( 'BFC_FILE', __FILE__ );
define( 'BFC_DIR', plugin_dir_path( __FILE__ ) );
define( 'BFC_URL', plugin_dir_url( __FILE__ ) );
define( 'BFC_SLUG', 'barrierefrei-check' );

/**
 * Minimal PSR-4 autoloader for the Kipphard\Barrierefrei\ namespace.
 * Maps Kipphard\Barrierefrei\Foo_Bar -> includes/class-foo-bar.php
 */
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'Kipphard\\Barrierefrei\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = 'class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
		$path     = BFC_DIR . 'includes/' . $file;
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

register_activation_hook( __FILE__, array( '\Kipphard\Barrierefrei\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\Kipphard\Barrierefrei\Plugin', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\Kipphard\Barrierefrei\Plugin::instance()->boot();
	}
);
