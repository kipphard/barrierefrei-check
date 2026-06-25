<?php
/**
 * WordPress admin UI: menus, pages, and POST handlers.
 *
 * @package Kipphard\Barrierefrei
 */

namespace Kipphard\Barrierefrei;

defined( 'ABSPATH' ) || exit;

/**
 * Registers admin menus and handles form submissions.
 */
class Admin {

	/**
	 * Register all WordPress hooks for the admin area.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_bfc_run_scan', array( $this, 'handle_run_scan' ) );
		add_action( 'admin_post_bfc_save_statement', array( $this, 'handle_save_statement' ) );
	}

	/**
	 * Register top-level menu and submenus.
	 */
	public function register_menus() {
		add_menu_page(
			__( 'Barrierefreiheit', 'barrierefrei-check' ),
			__( 'Barrierefreiheit', 'barrierefrei-check' ),
			Helpers::CAP,
			BFC_SLUG,
			array( $this, 'render_dashboard' ),
			'dashicons-universal-access-alt',
			80
		);

		add_submenu_page(
			BFC_SLUG,
			__( 'Dashboard', 'barrierefrei-check' ),
			__( 'Dashboard', 'barrierefrei-check' ),
			Helpers::CAP,
			BFC_SLUG,
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			BFC_SLUG,
			__( 'Erklärung', 'barrierefrei-check' ),
			__( 'Erklärung', 'barrierefrei-check' ),
			Helpers::CAP,
			'barrierefrei-check-statement',
			array( $this, 'render_statement' )
		);

		add_submenu_page(
			BFC_SLUG,
			__( 'Einstellungen', 'barrierefrei-check' ),
			__( 'Einstellungen', 'barrierefrei-check' ),
			Helpers::CAP,
			'barrierefrei-check-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Enqueue CSS and JS only on plugin admin pages.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		$plugin_hooks = array(
			'toplevel_page_' . BFC_SLUG,
			BFC_SLUG . '_page_barrierefrei-check-statement',
			BFC_SLUG . '_page_barrierefrei-check-settings',
		);
		if ( ! in_array( $hook, $plugin_hooks, true ) ) {
			return;
		}
		wp_enqueue_style(
			'bfc-admin',
			BFC_URL . 'assets/admin.css',
			array(),
			BFC_VERSION
		);
		wp_enqueue_script(
			'bfc-admin',
			BFC_URL . 'assets/admin.js',
			array(),
			BFC_VERSION,
			true
		);
	}

	// -------------------------------------------------------------------------
	// POST handlers
	// -------------------------------------------------------------------------

	/**
	 * Handle the "Scan starten" form submission.
	 */
	public function handle_run_scan() {
		Helpers::guard_post( 'bfc_run_scan' );

		$report = ( new Scanner() )->scan( Scanner::default_urls() );
		update_option( Helpers::OPT_LAST_REPORT, $report->to_array() );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => BFC_SLUG,
					'notice'  => 'scan_done',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle the Barrierefreiheitserklärung save form.
	 */
	public function handle_save_statement() {
		Helpers::guard_post( 'bfc_save_statement' );

		$clean = Helpers::sanitize_statement( $_POST );
		update_option( Helpers::OPT_STATEMENT, $clean );

		// Seite erstellen oder aktualisieren.
		$page_id = (int) get_option( 'bfc_statement_page_id', 0 );
		$page_data = array(
			'post_title'   => __( 'Erklärung zur Barrierefreiheit', 'barrierefrei-check' ),
			'post_content' => '[barrierefreiheitserklaerung]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
		);

		if ( $page_id && get_post( $page_id ) ) {
			$page_data['ID'] = $page_id;
			wp_update_post( $page_data );
		} else {
			$page_id = wp_insert_post( $page_data );
			if ( $page_id && ! is_wp_error( $page_id ) ) {
				update_option( 'bfc_statement_page_id', $page_id );
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => 'barrierefrei-check-statement',
					'notice' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// -------------------------------------------------------------------------
	// Page renderers
	// -------------------------------------------------------------------------

	/**
	 * Render the Dashboard page.
	 */
	public function render_dashboard() {
		if ( ! current_user_can( Helpers::CAP ) ) {
			return;
		}

		$report = Report::from_array( get_option( Helpers::OPT_LAST_REPORT ) );
		$notice = isset( $_GET['notice'] ) ? sanitize_key( $_GET['notice'] ) : '';
		?>
		<div class="wrap bfc-wrap">
			<h1><?php esc_html_e( 'Barrierefreiheit – Dashboard', 'barrierefrei-check' ); ?></h1>

			<?php if ( 'scan_done' === $notice ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Scan abgeschlossen. Die Ergebnisse werden unten angezeigt.', 'barrierefrei-check' ); ?></p>
				</div>
			<?php endif; ?>

			<?php $this->render_scan_form(); ?>

			<?php if ( null === $report ) : ?>
				<?php $this->render_empty_state(); ?>
			<?php else : ?>
				<?php $this->render_report( $report ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the "Scan starten" form.
	 */
	private function render_scan_form() {
		?>
		<div class="bfc-scan-box">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="bfc-scan-form">
				<input type="hidden" name="action" value="bfc_run_scan">
				<?php wp_nonce_field( 'bfc_run_scan' ); ?>
				<button type="submit" class="button button-primary bfc-scan-btn">
					<?php esc_html_e( 'Scan starten', 'barrierefrei-check' ); ?>
				</button>
				<span class="bfc-scan-running" style="display:none;">
					<?php esc_html_e( 'Scan läuft …', 'barrierefrei-check' ); ?>
				</span>
				<p class="description">
					<?php esc_html_e( 'Scannt die Startseite sowie bis zu 4 zuletzt veröffentlichte Seiten/Beiträge.', 'barrierefrei-check' ); ?>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the empty state when no scan has been run yet.
	 */
	private function render_empty_state() {
		?>
		<div class="bfc-empty-state">
			<span class="dashicons dashicons-universal-access-alt bfc-empty-icon"></span>
			<h2><?php esc_html_e( 'Noch kein Scan durchgeführt', 'barrierefrei-check' ); ?></h2>
			<p>
				<?php esc_html_e( 'Mit einem Scan prüft das Plugin das gerenderte HTML deiner Seiten auf häufige Barrierefreiheitsprobleme gemäß WCAG 2.2 und BFSG. Du erhältst eine priorisierte Liste von Problemen mit konkreten Lösungshinweisen.', 'barrierefrei-check' ); ?>
			</p>
			<p class="bfc-disclaimer">
				<strong><?php esc_html_e( 'Hinweis:', 'barrierefrei-check' ); ?></strong>
				<?php esc_html_e( 'Automatisierte Tests erkennen nur einen Teil der WCAG-Kriterien. Kriterien wie Farbkontrast (1.4.3), vollständige Tastaturbedienung oder kognitive Zugänglichkeit erfordern eine manuelle Prüfung. Dieses Plugin ersetzt kein vollständiges Barrierefreiheitsaudit.', 'barrierefrei-check' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the full scan report.
	 *
	 * @param Report $report The report to display.
	 */
	private function render_report( Report $report ) {
		$counts  = $report->counts_by_severity();
		$issues  = $report->sorted_issues();
		$scanned = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $report->scanned_at );
		?>
		<div class="bfc-report">

			<div class="bfc-score-row">
				<div class="bfc-score-gauge">
					<span class="bfc-score-number <?php echo esc_attr( $this->score_class( $report->score ) ); ?>">
						<?php echo esc_html( $report->score ); ?>
					</span>
					<span class="bfc-score-label"><?php esc_html_e( 'Score', 'barrierefrei-check' ); ?></span>
				</div>
				<div class="bfc-severity-badges">
					<?php foreach ( $counts as $sev => $n ) : ?>
						<span class="bfc-badge bfc-badge-<?php echo esc_attr( $sev ); ?>">
							<?php echo esc_html( Helpers::severity_label( $sev ) ); ?>:
							<strong><?php echo esc_html( $n ); ?></strong>
						</span>
					<?php endforeach; ?>
				</div>
				<p class="bfc-scanned-at">
					<?php
					printf(
						/* translators: %s: date and time of scan */
						esc_html__( 'Gescannt am: %s', 'barrierefrei-check' ),
						esc_html( $scanned )
					);
					?>
				</p>
			</div>

			<p class="bfc-disclaimer">
				<strong><?php esc_html_e( 'Wichtig:', 'barrierefrei-check' ); ?></strong>
				<?php esc_html_e( 'Automatisierte Tests erkennen nur einen Teil der WCAG-Kriterien. Kriterien wie Farbkontrast (1.4.3), vollständige Tastaturbedienung oder kognitive Zugänglichkeit erfordern eine manuelle Prüfung. Kein automatisches Werkzeug kann vollständige WCAG-Konformität garantieren.', 'barrierefrei-check' ); ?>
			</p>

			<?php if ( empty( $issues ) ) : ?>
				<div class="bfc-no-issues">
					<p><?php esc_html_e( 'Keine automatisch erkennbaren Probleme gefunden. Bitte führe trotzdem eine manuelle Prüfung durch.', 'barrierefrei-check' ); ?></p>
				</div>
			<?php else : ?>
				<h2><?php esc_html_e( 'Gefundene Probleme', 'barrierefrei-check' ); ?></h2>
				<div class="bfc-issue-list">
					<?php foreach ( $issues as $issue ) : ?>
						<div class="bfc-issue-card bfc-card-<?php echo esc_attr( $issue->severity ); ?>">
							<div class="bfc-issue-header">
								<span class="bfc-badge bfc-badge-<?php echo esc_attr( $issue->severity ); ?>">
									<?php echo esc_html( Helpers::severity_label( $issue->severity ) ); ?>
								</span>
								<span class="bfc-wcag-ref">WCAG <?php echo esc_html( $issue->wcag ); ?></span>
								<strong class="bfc-issue-title"><?php echo esc_html( $issue->title ); ?></strong>
								<?php if ( $issue->count > 1 ) : ?>
									<span class="bfc-issue-count">
										<?php
										printf(
											/* translators: %d: number of occurrences */
											esc_html__( '(%d Vorkommen)', 'barrierefrei-check' ),
											(int) $issue->count
										);
										?>
									</span>
								<?php endif; ?>
							</div>
							<div class="bfc-issue-body">
								<p class="bfc-issue-desc"><?php echo esc_html( $issue->description ); ?></p>
								<p class="bfc-issue-fix">
									<strong><?php esc_html_e( 'Lösung:', 'barrierefrei-check' ); ?></strong>
									<?php echo esc_html( $issue->how_to_fix ); ?>
								</p>
								<?php if ( '' !== $issue->url ) : ?>
									<p class="bfc-issue-url">
										<strong><?php esc_html_e( 'Seite:', 'barrierefrei-check' ); ?></strong>
										<a href="<?php echo esc_url( $issue->url ); ?>" target="_blank" rel="noopener noreferrer">
											<?php echo esc_html( $issue->url ); ?>
										</a>
									</p>
								<?php endif; ?>
								<?php if ( '' !== $issue->context ) : ?>
									<details class="bfc-issue-context-wrap">
										<summary><?php esc_html_e( 'Kontext anzeigen', 'barrierefrei-check' ); ?></summary>
										<pre class="bfc-issue-context"><code><?php echo esc_html( $issue->context ); ?></code></pre>
									</details>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $report->urls ) ) : ?>
				<details class="bfc-scanned-urls">
					<summary><?php esc_html_e( 'Gescannte URLs', 'barrierefrei-check' ); ?></summary>
					<ul>
						<?php foreach ( $report->urls as $scanned_url ) : ?>
							<li>
								<a href="<?php echo esc_url( $scanned_url ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo esc_html( $scanned_url ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</details>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Return a CSS class based on the score value.
	 *
	 * @param int $score 0–100.
	 * @return string
	 */
	private function score_class( $score ) {
		if ( $score >= 80 ) {
			return 'bfc-score-good';
		}
		if ( $score >= 50 ) {
			return 'bfc-score-medium';
		}
		return 'bfc-score-bad';
	}

	/**
	 * Render the Barrierefreiheitserklärung form page.
	 */
	public function render_statement() {
		if ( ! current_user_can( Helpers::CAP ) ) {
			return;
		}

		$fields  = (array) get_option( Helpers::OPT_STATEMENT, array() );
		$notice  = isset( $_GET['notice'] ) ? sanitize_key( $_GET['notice'] ) : '';
		$page_id = (int) get_option( 'bfc_statement_page_id', 0 );

		$conformance_options = array(
			'full'    => __( 'Vollständig konform', 'barrierefrei-check' ),
			'partial' => __( 'Teilweise konform', 'barrierefrei-check' ),
			'none'    => __( 'Nicht konform', 'barrierefrei-check' ),
		);
		?>
		<div class="wrap bfc-wrap">
			<h1><?php esc_html_e( 'Barrierefreiheitserklärung', 'barrierefrei-check' ); ?></h1>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php esc_html_e( 'Erklärung gespeichert.', 'barrierefrei-check' ); ?>
						<?php if ( $page_id && get_post( $page_id ) ) : ?>
							<a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Seite ansehen', 'barrierefrei-check' ); ?>
							</a>
						<?php endif; ?>
					</p>
				</div>
			<?php endif; ?>

			<p>
				<?php esc_html_e( 'Fülle die folgenden Felder aus. Das Plugin erzeugt oder aktualisiert automatisch eine WordPress-Seite mit dem Shortcode', 'barrierefrei-check' ); ?>
				<code>[barrierefreiheitserklaerung]</code>
				<?php esc_html_e( ', der die Erklärung rendert.', 'barrierefrei-check' ); ?>
			</p>

			<?php if ( $page_id && get_post( $page_id ) ) : ?>
				<p>
					<?php esc_html_e( 'Aktuelle Erklärungsseite:', 'barrierefrei-check' ); ?>
					<a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( get_permalink( $page_id ) ); ?>
					</a>
				</p>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="bfc_save_statement">
				<?php wp_nonce_field( 'bfc_save_statement' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="bfc-org-name"><?php esc_html_e( 'Organisation / Name der Website', 'barrierefrei-check' ); ?></label>
						</th>
						<td>
							<input type="text" id="bfc-org-name" name="org_name" class="regular-text"
								value="<?php echo esc_attr( isset( $fields['org_name'] ) ? $fields['org_name'] : '' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="bfc-contact-name"><?php esc_html_e( 'Ansprechperson (Name)', 'barrierefrei-check' ); ?></label>
						</th>
						<td>
							<input type="text" id="bfc-contact-name" name="contact_name" class="regular-text"
								value="<?php echo esc_attr( isset( $fields['contact_name'] ) ? $fields['contact_name'] : '' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="bfc-contact-email"><?php esc_html_e( 'Kontakt-E-Mail', 'barrierefrei-check' ); ?></label>
						</th>
						<td>
							<input type="email" id="bfc-contact-email" name="contact_email" class="regular-text"
								value="<?php echo esc_attr( isset( $fields['contact_email'] ) ? $fields['contact_email'] : '' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="bfc-contact-phone"><?php esc_html_e( 'Kontakt-Telefon', 'barrierefrei-check' ); ?></label>
						</th>
						<td>
							<input type="text" id="bfc-contact-phone" name="contact_phone" class="regular-text"
								value="<?php echo esc_attr( isset( $fields['contact_phone'] ) ? $fields['contact_phone'] : '' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="bfc-address"><?php esc_html_e( 'Adresse', 'barrierefrei-check' ); ?></label>
						</th>
						<td>
							<textarea id="bfc-address" name="address" rows="3" class="regular-text"><?php echo esc_textarea( isset( $fields['address'] ) ? $fields['address'] : '' ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="bfc-conformance"><?php esc_html_e( 'Konformitätsstatus', 'barrierefrei-check' ); ?></label>
						</th>
						<td>
							<select id="bfc-conformance" name="conformance">
								<?php foreach ( $conformance_options as $val => $label ) : ?>
									<option value="<?php echo esc_attr( $val ); ?>"
										<?php selected( isset( $fields['conformance'] ) ? $fields['conformance'] : 'partial', $val ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="bfc-enforcement-body"><?php esc_html_e( 'Durchsetzungsstelle / Schlichtungsstelle', 'barrierefrei-check' ); ?></label>
						</th>
						<td>
							<input type="text" id="bfc-enforcement-body" name="enforcement_body" class="regular-text"
								value="<?php echo esc_attr( isset( $fields['enforcement_body'] ) ? $fields['enforcement_body'] : '' ); ?>">
							<p class="description">
								<?php esc_html_e( 'Z. B. "Schlichtungsstelle nach dem BFSG, c/o Bundesnetzagentur"', 'barrierefrei-check' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Erklärung speichern', 'barrierefrei-check' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the Einstellungen (Settings) page with a Pro teaser.
	 */
	public function render_settings() {
		if ( ! current_user_can( Helpers::CAP ) ) {
			return;
		}
		?>
		<div class="wrap bfc-wrap">
			<h1><?php esc_html_e( 'Einstellungen', 'barrierefrei-check' ); ?></h1>

			<div class="bfc-settings-info card">
				<h2><?php esc_html_e( 'Kostenlose Version', 'barrierefrei-check' ); ?></h2>
				<p>
					<?php esc_html_e( 'Die kostenlose Version scannt automatisch die Startseite sowie bis zu 4 zuletzt veröffentlichte Seiten und Beiträge. Der Scan wird manuell über den Button im Dashboard gestartet.', 'barrierefrei-check' ); ?>
				</p>
				<ul>
					<li><?php esc_html_e( '12 automatisierte WCAG 2.2 / BFSG-Prüfregeln', 'barrierefrei-check' ); ?></li>
					<li><?php esc_html_e( 'Priorisierte Fehlerliste mit Lösungshinweisen', 'barrierefrei-check' ); ?></li>
					<li><?php esc_html_e( 'Automatisch generierte Barrierefreiheitserklärung', 'barrierefrei-check' ); ?></li>
				</ul>
			</div>

			<div class="bfc-pro-teaser card">
				<h2>
					<?php esc_html_e( 'Barrierefrei Pro', 'barrierefrei-check' ); ?>
					<span class="bfc-pro-badge"><?php esc_html_e( 'Demnächst', 'barrierefrei-check' ); ?></span>
				</h2>
				<p class="description">
					<?php esc_html_e( '(Geplante Funktionen – noch nicht verfügbar)', 'barrierefrei-check' ); ?>
				</p>
				<ul class="bfc-pro-features">
					<li>
						<span class="dashicons dashicons-clock"></span>
						<?php esc_html_e( 'Geplante automatische Scans (täglich/wöchentlich)', 'barrierefrei-check' ); ?>
					</li>
					<li>
						<span class="dashicons dashicons-pdf"></span>
						<?php esc_html_e( 'PDF-Konformitätsbericht zum Download', 'barrierefrei-check' ); ?>
					</li>
					<li>
						<span class="dashicons dashicons-networking"></span>
						<?php esc_html_e( 'Multisite-Unterstützung (alle Subseiten auf einen Blick)', 'barrierefrei-check' ); ?>
					</li>
					<li>
						<span class="dashicons dashicons-chart-line"></span>
						<?php esc_html_e( 'Kontinuierliches Monitoring mit Verlaufsdiagramm', 'barrierefrei-check' ); ?>
					</li>
				</ul>
				<p>
					<a href="https://products.kipphard.com/barrierefrei-check" target="_blank" rel="noopener noreferrer" class="button button-secondary">
						<?php esc_html_e( 'Mehr erfahren', 'barrierefrei-check' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}
}
