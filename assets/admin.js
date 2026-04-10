( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		const config = window.wpAiEditAdmin || {};
		const testBtn = document.getElementById( 'wp-ai-edit-test-connection' );
		const status = document.getElementById(
			'wp-ai-edit-connection-status'
		);
		const endpointInput = document.getElementById( 'wp-ai-edit-endpoint' );
		const apiKeyInput = document.getElementById( 'wp-ai-edit-api-key' );
		const modelSelect = document.getElementById( 'wp-ai-edit-model' );
		const tailTextarea = document.getElementById(
			'wp-ai-edit-tail-prompt'
		);
		const noTailActions = config.noTailActions || [];
		const separator = '\n\n' + '\u2500'.repeat( 40 ) + '\n[Tail Prompt]\n';

		// ── Test Connection ──
		if ( testBtn ) {
			testBtn.addEventListener( 'click', function () {
				status.textContent = config.i18n.testing;
				status.className = 'testing';
				testBtn.disabled = true;

				fetch( config.restUrl + 'test-connection', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': config.nonce,
					},
					body: JSON.stringify( {
						endpoint: endpointInput ? endpointInput.value : '',
						api_key: apiKeyInput ? apiKeyInput.value : '',
						model: modelSelect ? modelSelect.value : '',
					} ),
				} )
					.then( function ( res ) {
						return res.json();
					} )
					.then( function ( data ) {
						if ( data.success ) {
							status.textContent = config.i18n.success;
							status.className = 'success';
						} else {
							status.textContent =
								config.i18n.failed +
								' ' +
								( data.message || '' );
							status.className = 'error';
						}
					} )
					.catch( function ( err ) {
						status.textContent =
							config.i18n.failed + ' ' + err.message;
						status.className = 'error';
					} )
					.finally( function () {
						testBtn.disabled = false;
					} );
			} );
		}

		// ── Reset prompt buttons ──
		document
			.querySelectorAll( '.wp-ai-edit-reset-prompt' )
			.forEach( function ( btn ) {
				btn.addEventListener( 'click', function () {
					// eslint-disable-next-line no-alert
					if ( ! window.confirm( config.i18n.resetConfirm ) ) {
						return;
					}
					const target = btn.getAttribute( 'data-target' );
					let textarea;
					if ( target === 'tail_prompt' ) {
						textarea = document.getElementById(
							'wp-ai-edit-tail-prompt'
						);
					} else {
						textarea = document.querySelector(
							'textarea[name="wp_ai_edit_settings[' +
								target +
								']"]'
						);
					}
					if ( textarea ) {
						textarea.value =
							textarea.getAttribute( 'data-default' ) || '';
						textarea.dispatchEvent( new Event( 'input' ) );
					}
				} );
			} );

		// ── Prompt Preview ──
		function updateAllPreviews() {
			var tailPrompt = tailTextarea ? tailTextarea.value : '';

			document
				.querySelectorAll( '.wp-ai-edit-prompt-preview' )
				.forEach( function ( details ) {
					var actionKey = details.getAttribute( 'data-action' );
					var textarea = document.querySelector(
						'textarea[data-action="' + actionKey + '"]'
					);
					var pre = details.querySelector(
						'.wp-ai-edit-preview-content'
					);

					if ( ! textarea || ! pre ) {
						return;
					}

					var promptText =
						textarea.value ||
						textarea.getAttribute( 'data-default' ) ||
						'';

					if ( noTailActions.indexOf( actionKey ) !== -1 ) {
						pre.textContent = promptText;
					} else {
						pre.textContent =
							promptText + separator + tailPrompt;
					}
				} );
		}

		// Attach input listeners to all prompt textareas
		document
			.querySelectorAll( '.wp-ai-edit-prompt-textarea' )
			.forEach( function ( textarea ) {
				textarea.addEventListener( 'input', updateAllPreviews );
			} );

		// Initial preview render
		updateAllPreviews();
	} );
} )();
