/* Barrierefrei Check – Admin JS */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var form = document.getElementById( 'bfc-scan-form' );
		if ( ! form ) {
			return;
		}

		var btn     = form.querySelector( '.bfc-scan-btn' );
		var running = form.querySelector( '.bfc-scan-running' );

		form.addEventListener( 'submit', function () {
			if ( btn ) {
				btn.disabled = true;
				btn.style.opacity = '0.6';
			}
			if ( running ) {
				running.style.display = 'inline';
			}
		} );
	} );
}() );
