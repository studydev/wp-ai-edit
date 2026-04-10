// Handles SSE responses from the AI generate endpoint.
export async function streamGenerate(
	{ action, text, command = '' },
	callbacks
) {
	const { onChunk, onDone, onError } = callbacks;

	const url = `${ window.wpAiEdit.restUrl }generate`;

	let response;
	try {
		response = await fetch( url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': window.wpAiEdit.nonce,
			},
			body: JSON.stringify( { action, text, command } ),
		} );
	} catch ( err ) {
		onError( err.message );
		return;
	}

	if (
		! response.ok &&
		response.headers
			.get( 'content-type' )
			?.indexOf( 'text/event-stream' ) === -1
	) {
		const errorBody = await response.text();
		onError( `HTTP ${ response.status }: ${ errorBody }` );
		return;
	}

	const reader = response.body.getReader();
	const decoder = new TextDecoder();
	let buffer = '';

	try {
		while ( true ) {
			const { done, value } = await reader.read();

			if ( done ) {
				onDone();
				break;
			}

			buffer += decoder.decode( value, { stream: true } );
			const lines = buffer.split( '\n' );
			// Keep the last potentially incomplete line in the buffer
			buffer = lines.pop() || '';

			for ( const line of lines ) {
				const trimmed = line.trim();

				if ( trimmed === '' ) {
					continue;
				}

				if ( ! trimmed.startsWith( 'data: ' ) ) {
					continue;
				}

				const dataStr = trimmed.slice( 6 );

				if ( dataStr === '[DONE]' ) {
					onDone();
					return;
				}

				try {
					const data = JSON.parse( dataStr );

					if ( data.error ) {
						onError( data.error );
						return;
					}

					if ( data.content ) {
						onChunk( data.content );
					}
				} catch {
					// Skip malformed JSON lines
				}
			}
		}
	} catch ( err ) {
		onError( err.message );
	}
}
