<?php
/**
 * Aggregated scan result: the issues found, the score, and per-severity counts.
 *
 * @package Kipphard\Barrierefrei
 */

namespace Kipphard\Barrierefrei;

defined( 'ABSPATH' ) || exit;

/**
 * Result of scanning one or more URLs.
 */
class Report {

	/** @var Issue[] */
	public $issues;

	/** @var string[] URLs that were scanned. */
	public $urls;

	/** @var int 0–100 accessibility score. */
	public $score;

	/** @var int Unix timestamp of the scan. */
	public $scanned_at;

	/**
	 * @param Issue[]  $issues Issues found.
	 * @param string[] $urls   Scanned URLs.
	 */
	public function __construct( array $issues, array $urls ) {
		$this->issues     = $issues;
		$this->urls       = $urls;
		$this->score      = Helpers::score( $issues );
		$this->scanned_at = time();
	}

	/**
	 * Count issues per severity.
	 *
	 * @return array<string,int>
	 */
	public function counts_by_severity() {
		$counts = array(
			'critical' => 0,
			'serious'  => 0,
			'moderate' => 0,
			'minor'    => 0,
		);
		foreach ( $this->issues as $issue ) {
			if ( isset( $counts[ $issue->severity ] ) ) {
				$counts[ $issue->severity ] += max( 1, (int) $issue->count );
			}
		}
		return $counts;
	}

	/**
	 * Issues sorted worst-first (by severity weight, then occurrence count).
	 *
	 * @return Issue[]
	 */
	public function sorted_issues() {
		$weights = Helpers::severity_weights();
		$issues  = $this->issues;
		usort(
			$issues,
			static function ( $a, $b ) use ( $weights ) {
				$wa = isset( $weights[ $a->severity ] ) ? $weights[ $a->severity ] : 0;
				$wb = isset( $weights[ $b->severity ] ) ? $weights[ $b->severity ] : 0;
				if ( $wa === $wb ) {
					return (int) $b->count - (int) $a->count;
				}
				return $wb - $wa;
			}
		);
		return $issues;
	}

	/**
	 * Serialize to a storable array.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return array(
			'issues'     => array_map(
				static function ( Issue $i ) {
					return $i->to_array();
				},
				$this->issues
			),
			'urls'       => $this->urls,
			'score'      => $this->score,
			'scanned_at' => $this->scanned_at,
		);
	}

	/**
	 * Rebuild a Report from stored data.
	 *
	 * @param mixed $data Stored array.
	 * @return self|null
	 */
	public static function from_array( $data ) {
		if ( ! is_array( $data ) || ! isset( $data['issues'] ) ) {
			return null;
		}
		$issues = array_map(
			static function ( $a ) {
				return Issue::from_array( (array) $a );
			},
			(array) $data['issues']
		);
		$report             = new self( $issues, isset( $data['urls'] ) ? (array) $data['urls'] : array() );
		$report->score      = isset( $data['score'] ) ? (int) $data['score'] : $report->score;
		$report->scanned_at = isset( $data['scanned_at'] ) ? (int) $data['scanned_at'] : $report->scanned_at;
		return $report;
	}
}
