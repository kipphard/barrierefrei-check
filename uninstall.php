<?php
/**
 * Plugin uninstall routine: remove all plugin options from the database.
 *
 * @package Kipphard\Barrierefrei
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'bfc_statement' );
delete_option( 'bfc_last_report' );
delete_option( 'bfc_statement_page_id' );
