<?php
/**
 * Shortcode and renderer for the Barrierefreiheitserklärung.
 *
 * @package Kipphard\Barrierefrei
 */

namespace Kipphard\Barrierefrei;

defined( 'ABSPATH' ) || exit;

/**
 * Generates the BFSG-conformant Barrierefreiheitserklärung as HTML.
 */
class Statement {

	/**
	 * Shortcode callback: [barrierefreiheitserklaerung]
	 *
	 * @param mixed $atts Shortcode attributes (unused).
	 * @return string Rendered HTML.
	 */
	public static function shortcode( $atts ) {
		$fields = (array) get_option( Helpers::OPT_STATEMENT, array() );
		if ( empty( $fields ) ) {
			return '<p>' . esc_html__( 'Keine Barrierefreiheitserklärung hinterlegt. Bitte fülle die Felder unter Barrierefreiheit → Erklärung aus.', 'barrierefrei-check' ) . '</p>';
		}
		return self::render( $fields );
	}

	/**
	 * Build the full Barrierefreiheitserklärung HTML string.
	 *
	 * @param array<string,string> $fields Sanitized statement fields.
	 * @return string
	 */
	public static function render( array $fields ) {
		$org_name        = isset( $fields['org_name'] ) ? $fields['org_name'] : '';
		$contact_name    = isset( $fields['contact_name'] ) ? $fields['contact_name'] : '';
		$contact_email   = isset( $fields['contact_email'] ) ? $fields['contact_email'] : '';
		$contact_phone   = isset( $fields['contact_phone'] ) ? $fields['contact_phone'] : '';
		$conformance     = isset( $fields['conformance'] ) ? $fields['conformance'] : 'partial';
		$address         = isset( $fields['address'] ) ? $fields['address'] : '';
		$enforcement     = isset( $fields['enforcement_body'] ) ? $fields['enforcement_body'] : '';
		$date_str        = date_i18n(
			get_option( 'date_format' ),
			current_time( 'timestamp' )
		);

		$conformance_map = array(
			'full'    => __( 'vollständig vereinbar', 'barrierefrei-check' ),
			'partial' => __( 'teilweise vereinbar', 'barrierefrei-check' ),
			'none'    => __( 'nicht vereinbar', 'barrierefrei-check' ),
		);
		$conformance_label = isset( $conformance_map[ $conformance ] )
			? $conformance_map[ $conformance ]
			: $conformance_map['partial'];

		ob_start();
		?>
		<div class="bfc-statement">

			<h2><?php esc_html_e( 'Erklärung zur Barrierefreiheit', 'barrierefrei-check' ); ?></h2>

			<p>
				<?php
				printf(
					/* translators: %s: organisation name */
					esc_html__( '%s ist bemüht, die eigene Website im Einklang mit dem Barrierefreiheitsstärkungsgesetz (BFSG) und der EU-Richtlinie 2019/882 barrierefrei zugänglich zu machen. Diese Erklärung zur Barrierefreiheit gilt für die vorliegende Website.', 'barrierefrei-check' ),
					esc_html( $org_name )
				);
				?>
			</p>

			<h3><?php esc_html_e( 'Stand der Vereinbarkeit', 'barrierefrei-check' ); ?></h3>

			<p>
				<?php
				printf(
					/* translators: %s: conformance label (vollständig/teilweise/nicht vereinbar) */
					esc_html__( 'Diese Website ist mit den Anforderungen der WCAG 2.2 auf den Stufen A und AA %s.', 'barrierefrei-check' ),
					esc_html( $conformance_label )
				);
				?>
			</p>

			<?php if ( 'partial' === $conformance ) : ?>
				<p>
					<?php esc_html_e( 'Folgende Inhalte oder Bereiche sind noch nicht vollständig barrierefrei zugänglich (bitte hier die konkreten nicht-konformen Inhalte ergänzen):', 'barrierefrei-check' ); ?>
				</p>
				<ul>
					<li><?php esc_html_e( '[Bitte konkrete nicht-konforme Inhalte und Bereiche eintragen]', 'barrierefrei-check' ); ?></li>
				</ul>
			<?php endif; ?>

			<?php if ( 'none' === $conformance ) : ?>
				<p>
					<?php esc_html_e( 'Die Website erfüllt derzeit die Anforderungen der WCAG 2.2 nicht. Wir arbeiten daran, die Barrierefreiheit der Website zu verbessern.', 'barrierefrei-check' ); ?>
				</p>
			<?php endif; ?>

			<h3><?php esc_html_e( 'Kontakt und Feedback', 'barrierefrei-check' ); ?></h3>

			<p>
				<?php esc_html_e( 'Wenn Sie auf Barrieren stoßen oder Feedback zur Barrierefreiheit dieser Website geben möchten, wenden Sie sich bitte an:', 'barrierefrei-check' ); ?>
			</p>

			<address>
				<?php if ( '' !== $org_name ) : ?>
					<strong><?php echo esc_html( $org_name ); ?></strong><br>
				<?php endif; ?>
				<?php if ( '' !== $contact_name ) : ?>
					<?php echo esc_html( $contact_name ); ?><br>
				<?php endif; ?>
				<?php if ( '' !== $address ) : ?>
					<?php echo nl2br( esc_html( $address ) ); ?><br>
				<?php endif; ?>
				<?php if ( '' !== $contact_email ) : ?>
					<?php esc_html_e( 'E-Mail:', 'barrierefrei-check' ); ?>
					<a href="mailto:<?php echo esc_attr( $contact_email ); ?>">
						<?php echo esc_html( $contact_email ); ?>
					</a><br>
				<?php endif; ?>
				<?php if ( '' !== $contact_phone ) : ?>
					<?php esc_html_e( 'Telefon:', 'barrierefrei-check' ); ?>
					<?php echo esc_html( $contact_phone ); ?><br>
				<?php endif; ?>
			</address>

			<h3><?php esc_html_e( 'Durchsetzungsverfahren', 'barrierefrei-check' ); ?></h3>

			<p>
				<?php esc_html_e( 'Wenn Sie auf Ihre Anfrage zur Barrierefreiheit innerhalb von sechs Wochen keine zufriedenstellende Antwort erhalten haben, können Sie die zuständige Durchsetzungs- oder Schlichtungsstelle einschalten:', 'barrierefrei-check' ); ?>
			</p>

			<?php if ( '' !== $enforcement ) : ?>
				<p><strong><?php echo esc_html( $enforcement ); ?></strong></p>
			<?php else : ?>
				<p>
					<?php esc_html_e( '[Bitte die zuständige Durchsetzungs- oder Schlichtungsstelle eintragen]', 'barrierefrei-check' ); ?>
				</p>
			<?php endif; ?>

			<p>
				<?php esc_html_e( 'Im Rahmen des Schlichtungsverfahrens können Sie eine außergerichtliche Einigung anstreben.', 'barrierefrei-check' ); ?>
			</p>

			<p class="bfc-statement-date">
				<?php
				printf(
					/* translators: %s: creation date */
					esc_html__( 'Diese Erklärung wurde erstellt am: %s', 'barrierefrei-check' ),
					esc_html( $date_str )
				);
				?>
			</p>

			<p>
				<em>
					<?php esc_html_e( 'Hinweis: Dieser Text ist ein Vorlage-Entwurf auf Basis des BFSG und der EU-Richtlinie 2019/882. Er sollte vor Veröffentlichung auf Vollständigkeit und rechtliche Korrektheit durch eine sachkundige Person geprüft werden.', 'barrierefrei-check' ); ?>
				</em>
			</p>

		</div>
		<?php
		return ob_get_clean();
	}
}
