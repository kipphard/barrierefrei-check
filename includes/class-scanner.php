<?php
/**
 * Fetches pages and orchestrates rule evaluation across multiple URLs.
 *
 * @package Kipphard\Barrierefrei
 */

namespace Kipphard\Barrierefrei;

defined( 'ABSPATH' ) || exit;

/**
 * HTTP fetcher + DOM parser that aggregates Issues into a Report.
 */
class Scanner {

	/**
	 * Scan a list of URLs and return a combined Report.
	 *
	 * @param string[] $urls URLs to scan.
	 * @return Report
	 */
	public function scan( array $urls ) {
		$all_issues   = array();
		$scanned_urls = array();

		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );

		foreach ( $urls as $url ) {
			// SSRF-Schutz: nur URLs scannen, die zum eigenen Host gehören.
			$url_host = wp_parse_url( $url, PHP_URL_HOST );
			if ( empty( $url_host ) || $url_host !== $home_host ) {
				continue;
			}

			$response = wp_remote_get(
				$url,
				array(
					'timeout'     => 15,
					'redirection' => 3,
					'sslverify'   => true,
					'user-agent'  => 'barrierefrei-check/' . BFC_VERSION,
				)
			);

			if ( is_wp_error( $response ) ) {
				// Fehler protokollieren, aber nicht abbrechen.
				error_log( 'BFC Scanner: Fehler beim Abrufen von ' . $url . ': ' . $response->get_error_message() );
				continue;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( 200 !== $code ) {
				error_log( 'BFC Scanner: HTTP ' . $code . ' für ' . $url );
				continue;
			}

			$body = wp_remote_retrieve_body( $response );
			if ( empty( $body ) ) {
				continue;
			}

			$dom   = self::parse_html( $body );
			$xpath = new \DOMXPath( $dom );

			$issues     = Rules::run( $dom, $xpath, $url );
			$all_issues = array_merge( $all_issues, $issues );

			$scanned_urls[] = $url;
		}

		return new Report( $all_issues, $scanned_urls );
	}

	/**
	 * Build a default list of URLs to scan: home + up to $limit-1 recent pages/posts.
	 *
	 * @param int $limit Max number of URLs (including home).
	 * @return string[]
	 */
	public static function default_urls( $limit = 5 ) {
		$urls = array( home_url( '/' ) );

		$posts = get_posts(
			array(
				'post_type'      => array( 'page', 'post' ),
				'post_status'    => 'publish',
				'numberposts'    => $limit - 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);

		foreach ( $posts as $post_id ) {
			$permalink = get_permalink( $post_id );
			if ( $permalink && ! in_array( $permalink, $urls, true ) ) {
				$urls[] = $permalink;
			}
		}

		return $urls;
	}

	/**
	 * Parse an HTML string into a DOMDocument.
	 *
	 * @param string $html Raw HTML body.
	 * @return \DOMDocument
	 */
	private static function parse_html( $html ) {
		libxml_use_internal_errors( true );
		$dom = new \DOMDocument();
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
		libxml_clear_errors();
		return $dom;
	}
}
