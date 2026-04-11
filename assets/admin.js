( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		const config = window.wpAiEditAdmin || {};
		const activeProviderInput = document.getElementById(
			'wp-ai-edit-active-provider'
		);
		const providerTabs = document.querySelectorAll(
			'.wp-ai-edit-provider-tab'
		);
		const providerSwitchStatus = document.getElementById(
			'wp-ai-edit-provider-switch-status'
		);
		const testButtons = document.querySelectorAll(
			'.wp-ai-edit-test-connection'
		);
		const tailTextarea = document.getElementById(
			'wp-ai-edit-tail-prompt'
		);
		const noTailActions = config.noTailActions || [];
		const separator = '\n\n' + '\u2500'.repeat( 40 ) + '\n[Tail Prompt]\n';

		function getActiveProvider() {
			if ( activeProviderInput && activeProviderInput.value ) {
				return activeProviderInput.value;
			}

			if ( providerTabs.length > 0 ) {
				return providerTabs[ 0 ].getAttribute( 'data-provider' ) || '';
			}

			return '';
		}

		function setActiveProviderUI( provider ) {
			if ( activeProviderInput ) {
				activeProviderInput.value = provider;
			}

			providerTabs.forEach( function ( tab ) {
				const isActive =
					tab.getAttribute( 'data-provider' ) === provider;
				tab.classList.toggle( 'is-active', isActive );
				tab.setAttribute(
					'aria-selected',
					isActive ? 'true' : 'false'
				);
			} );

			document
				.querySelectorAll( '.wp-ai-edit-provider-panel' )
				.forEach( function ( panel ) {
					const isActive =
						panel.getAttribute( 'data-provider' ) === provider;
					panel.hidden = ! isActive;
					panel.classList.toggle( 'is-active', isActive );
				} );
		}

		function persistActiveProvider( provider ) {
			return fetch( config.restUrl + 'active-provider', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': config.nonce,
				},
				body: JSON.stringify( { provider } ),
			} ).then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'provider-switch-failed' );
				}

				return response.json();
			} );
		}

		providerTabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				const provider = tab.getAttribute( 'data-provider' ) || '';

				if ( ! provider ) {
					return;
				}

				setActiveProviderUI( provider );

				if ( providerSwitchStatus ) {
					providerSwitchStatus.textContent = '';
					providerSwitchStatus.className =
						'description wp-ai-edit-provider-switch-status';
				}

				persistActiveProvider( provider ).catch( function () {
					if ( providerSwitchStatus ) {
						providerSwitchStatus.textContent =
							config.i18n.providerSwitchFailed;
						providerSwitchStatus.className =
							'description wp-ai-edit-provider-switch-status is-error';
					}
				} );
			} );
		} );

		testButtons.forEach( function ( testBtn ) {
			testBtn.addEventListener( 'click', function () {
				const provider =
					testBtn.getAttribute( 'data-provider' ) ||
					getActiveProvider();
				const status = document.getElementById(
					'wp-ai-edit-connection-status-' + provider
				);

				if ( ! status ) {
					return;
				}

				const endpointInput = document.getElementById(
					'wp-ai-edit-endpoint-' + provider
				);
				const apiKeyInput = document.getElementById(
					'wp-ai-edit-api-key-' + provider
				);
				const modelInput = document.getElementById(
					'wp-ai-edit-model-' + provider
				);

				status.textContent = config.i18n.testing;
				status.className = 'wp-ai-edit-connection-status testing';
				testBtn.disabled = true;

				fetch( config.restUrl + 'test-connection', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': config.nonce,
					},
					body: JSON.stringify( {
						provider,
						endpoint: endpointInput ? endpointInput.value : '',
						api_key: apiKeyInput ? apiKeyInput.value : '',
						model: modelInput ? modelInput.value : '',
					} ),
				} )
					.then( function ( res ) {
						return res.json();
					} )
					.then( function ( data ) {
						if ( data.success ) {
							status.textContent = config.i18n.success;
							status.className =
								'wp-ai-edit-connection-status success';
						} else {
							status.textContent =
								config.i18n.failed +
								' ' +
								( data.message || '' );
							status.className =
								'wp-ai-edit-connection-status error';
						}
					} )
					.catch( function ( err ) {
						status.textContent =
							config.i18n.failed + ' ' + err.message;
						status.className = 'wp-ai-edit-connection-status error';
					} )
					.finally( function () {
						testBtn.disabled = false;
					} );
			} );
		} );

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

		function updateAllPreviews() {
			const tailPrompt = tailTextarea ? tailTextarea.value : '';

			document
				.querySelectorAll( '.wp-ai-edit-prompt-preview' )
				.forEach( function ( details ) {
					const actionKey = details.getAttribute( 'data-action' );
					const textarea = document.querySelector(
						'textarea[data-action="' + actionKey + '"]'
					);
					const pre = details.querySelector(
						'.wp-ai-edit-preview-content'
					);

					if ( ! textarea || ! pre ) {
						return;
					}

					const promptText =
						textarea.value ||
						textarea.getAttribute( 'data-default' ) ||
						'';

					if ( noTailActions.indexOf( actionKey ) !== -1 ) {
						pre.textContent = promptText;
					} else {
						pre.textContent = promptText + separator + tailPrompt;
					}
				} );
		}

		document
			.querySelectorAll( '.wp-ai-edit-prompt-textarea' )
			.forEach( function ( textarea ) {
				textarea.addEventListener( 'input', updateAllPreviews );
			} );

		setActiveProviderUI( getActiveProvider() );

		// ── Model Tab Buttons ──
		document
			.querySelectorAll( '.wp-ai-edit-model-tab' )
			.forEach( function ( btn ) {
				btn.addEventListener( 'click', function () {
					const modelValue = btn.getAttribute( 'data-model' );
					const tabsContainer = btn.closest(
						'.wp-ai-edit-model-tabs'
					);
					const provider =
						tabsContainer.getAttribute( 'data-provider' );
					const hiddenInput = document.getElementById(
						'wp-ai-edit-model-' + provider
					);

					if ( hiddenInput ) {
						hiddenInput.value = modelValue;
					}

					// Warn when selecting expensive Pro model
					if ( modelValue === 'gpt-5.4-pro' ) {
						/* eslint-disable no-alert */
						if (
							! window.confirm(
								config.i18n.proModelWarning ||
									'GPT-5.4 Pro costs $30/MTok input and $180/MTok output. Continue?'
							)
						) {
							/* eslint-enable no-alert */
							// Revert to previous model
							const prevActive = tabsContainer.querySelector(
								'.wp-ai-edit-model-tab.is-active'
							);
							if ( prevActive && hiddenInput ) {
								hiddenInput.value =
									prevActive.getAttribute( 'data-model' ) ||
									'';
							}
							return;
						}
						/* eslint-enable no-alert */
					}

					tabsContainer
						.querySelectorAll( '.wp-ai-edit-model-tab' )
						.forEach( function ( sibling ) {
							sibling.classList.toggle(
								'is-active',
								sibling === btn
							);
						} );
				} );
			} );

		updateAllPreviews();
	} );
} )();
