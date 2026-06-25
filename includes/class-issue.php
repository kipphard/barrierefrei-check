<?php
/**
 * Value object for a single accessibility finding.
 *
 * @package Kipphard\Barrierefrei
 */

namespace Kipphard\Barrierefrei;

defined( 'ABSPATH' ) || exit;

/**
 * One accessibility issue found on a scanned page.
 */
class Issue {

	/** @var string Stable rule id, e.g. 'img_alt_missing'. */
	public $rule_id;

	/** @var string Severity: critical|serious|moderate|minor. */
	public $severity;

	/** @var string WCAG success criterion, e.g. '1.1.1'. */
	public $wcag;

	/** @var string Short, translated title. */
	public $title;

	/** @var string Translated explanation of the problem. */
	public $description;

	/** @var string Translated, concrete fix guidance. */
	public $how_to_fix;

	/** @var string URL of the page where this was found. */
	public $url;

	/** @var string Short HTML/context snippet illustrating the issue. */
	public $context;

	/** @var int Number of occurrences of this rule on the page. */
	public $count;

	/**
	 * @param array<string,mixed> $args Issue fields.
	 */
	public function __construct( array $args ) {
		$this->rule_id     = isset( $args['rule_id'] ) ? (string) $args['rule_id'] : '';
		$this->severity    = isset( $args['severity'] ) ? (string) $args['severity'] : 'minor';
		$this->wcag        = isset( $args['wcag'] ) ? (string) $args['wcag'] : '';
		$this->title       = isset( $args['title'] ) ? (string) $args['title'] : '';
		$this->description  = isset( $args['description'] ) ? (string) $args['description'] : '';
		$this->how_to_fix  = isset( $args['how_to_fix'] ) ? (string) $args['how_to_fix'] : '';
		$this->url         = isset( $args['url'] ) ? (string) $args['url'] : '';
		$this->context     = isset( $args['context'] ) ? (string) $args['context'] : '';
		$this->count       = isset( $args['count'] ) ? (int) $args['count'] : 1;
	}

	/**
	 * Serialize for storage in an option.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return array(
			'rule_id'    => $this->rule_id,
			'severity'   => $this->severity,
			'wcag'       => $this->wcag,
			'title'      => $this->title,
			'description' => $this->description,
			'how_to_fix' => $this->how_to_fix,
			'url'        => $this->url,
			'context'    => $this->context,
			'count'      => $this->count,
		);
	}

	/**
	 * Rebuild from a stored array.
	 *
	 * @param array<string,mixed> $data Stored data.
	 * @return self
	 */
	public static function from_array( array $data ) {
		return new self( $data );
	}
}
