<?php
/**
 * Shared helpers: capability, severity model, scoring, option sanitization.
 *
 * @package Kipphard\Barrierefrei
 */

namespace Kipphard\Barrierefrei;

defined( 'ABSPATH' ) || exit;

/**
 * Stateless helper methods used across the plugin.
 */
class Helpers {

	/** Capability required for all admin actions. */
	const CAP = 'manage_options';

	/** Option key holding the saved Barrierefreiheitserklärung fields. */
	const OPT_STATEMENT = 'bfc_statement';

	/** Option key holding the last scan report. */
	const OPT_LAST_REPORT = 'bfc_last_report';

	/**
	 * Penalty weight per severity. Higher = worse for the score.
	 *
	 * @return array<string,int>
	 */
	public static function severity_weights() {
		return array(
			'critical' => 10,
			'serious'  => 6,
			'moderate' => 3,
			'minor'    => 1,
		);
	}

	/**
	 * Human label per severity (translated).
	 *
	 * @param string $severity Severity key.
	 * @return string
	 */
	public static function severity_label( $severity ) {
		$labels = array(
			'critical' => __( 'Kritisch', 'barrierefrei-check' ),
			'serious'  => __( 'Schwerwiegend', 'barrierefrei-check' ),
			'moderate' => __( 'Mittel', 'barrierefrei-check' ),
			'minor'    => __( 'Gering', 'barrierefrei-check' ),
		);
		return isset( $labels[ $severity ] ) ? $labels[ $severity ] : $severity;
	}

	/**
	 * Compute a 0–100 accessibility score from a list of issues.
	 * Starts at 100 and subtracts the weighted penalty of every issue occurrence,
	 * floored at 0. Transparent and monotonic: more/worse issues → lower score.
	 *
	 * @param Issue[] $issues Issues found.
	 * @return int
	 */
	public static function score( array $issues ) {
		$weights = self::severity_weights();
		$penalty = 0;
		foreach ( $issues as $issue ) {
			$w        = isset( $weights[ $issue->severity ] ) ? $weights[ $issue->severity ] : 1;
			$penalty += $w * max( 1, (int) $issue->count );
		}
		return (int) max( 0, 100 - $penalty );
	}

	/**
	 * Verify an admin POST request: capability + nonce. Dies on failure.
	 *
	 * @param string $action Nonce action.
	 * @param string $field  Nonce field name.
	 */
	public static function guard_post( $action, $field = '_wpnonce' ) {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'barrierefrei-check' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $action, $field );
	}

	/**
	 * Sanitize the Barrierefreiheitserklärung form fields.
	 *
	 * @param array<string,mixed> $raw Raw $_POST subset.
	 * @return array<string,string>
	 */
	public static function sanitize_statement( array $raw ) {
		$fields = array(
			'org_name',
			'contact_name',
			'contact_email',
			'contact_phone',
			'conformance', // 'full' | 'partial' | 'none'
			'address',
			'enforcement_body',
		);
		$clean = array();
		foreach ( $fields as $f ) {
			$value = isset( $raw[ $f ] ) ? wp_unslash( $raw[ $f ] ) : '';
			if ( 'contact_email' === $f ) {
				$clean[ $f ] = sanitize_email( $value );
			} elseif ( 'address' === $f ) {
				$clean[ $f ] = sanitize_textarea_field( $value );
			} else {
				$clean[ $f ] = sanitize_text_field( $value );
			}
		}
		return $clean;
	}
}
