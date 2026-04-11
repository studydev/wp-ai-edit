import { Button, Spinner, Flex, FlexItem } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import DOMPurify from 'dompurify';
import { STORE_NAME } from '../store';

const ALLOWED_TAGS = [
	'p',
	'strong',
	'b',
	'em',
	'i',
	'a',
	'ul',
	'ol',
	'li',
	'blockquote',
	'h2',
	'h3',
	'h4',
	'code',
	'span',
	'br',
];

const ALLOWED_ATTR = [ 'href', 'target', 'rel', 'class' ];

const stripCodeFences = ( text ) =>
	text
		.replace( /^```(?:html|\w*)\s*\n?/gm, '' )
		.replace( /\n?```\s*$/gm, '' );

const sanitizeHtml = ( html ) =>
	DOMPurify.sanitize( stripCodeFences( html ), {
		ALLOWED_TAGS,
		ALLOWED_ATTR,
	} );

export default function ResultPopover( {
	onUse,
	onRegenerate,
	onWriteMore,
	onClose,
	isSeo = false,
} ) {
	const { isLoading, streamedText, error } = useSelect( ( sel ) => {
		const store = sel( STORE_NAME );
		return {
			isLoading: store.isLoading(),
			streamedText: store.getStreamedText(),
			error: store.getError(),
		};
	}, [] );

	const { reset } = useDispatch( STORE_NAME );

	const handleClose = () => {
		reset();
		onClose();
	};

	const renderContent = () => {
		if ( streamedText ) {
			const cleaned = sanitizeHtml( streamedText );
			const htmlWithCursor = isLoading
				? cleaned + '<span class="wp-ai-edit-cursor">|</span>'
				: cleaned;
			return (
				<div
					className="wp-ai-edit-streamed-text"
					dangerouslySetInnerHTML={ { __html: htmlWithCursor } }
				/>
			);
		}
		if ( isLoading ) {
			return (
				<span className="wp-ai-edit-placeholder">
					{ __( 'Generating\u2026', 'wp-ai-edit' ) }
				</span>
			);
		}
		return null;
	};

	return (
		<div className="wp-ai-edit-result-popover">
			<div className="wp-ai-edit-result-content">
				{ error && (
					<div className="wp-ai-edit-error">
						<p>{ error }</p>
					</div>
				) }

				{ ! error && renderContent() }
			</div>

			{ isLoading && (
				<div className="wp-ai-edit-loading-bar">
					<Spinner />
				</div>
			) }

			{ ! isLoading && ( streamedText || error ) && (
				<Flex
					className="wp-ai-edit-result-actions"
					justify="flex-start"
					gap={ 2 }
				>
					{ streamedText && ! error && ! isSeo && (
						<>
							<FlexItem>
								<Button
									variant="primary"
									onClick={ onUse }
									size="compact"
								>
									{ __( 'Use', 'wp-ai-edit' ) }
								</Button>
							</FlexItem>
							<FlexItem>
								<Button
									variant="secondary"
									onClick={ onRegenerate }
									size="compact"
								>
									{ __( 'Regenerate', 'wp-ai-edit' ) }
								</Button>
							</FlexItem>
							<FlexItem>
								<Button
									variant="secondary"
									onClick={ onWriteMore }
									size="compact"
								>
									{ __( 'Write More', 'wp-ai-edit' ) }
								</Button>
							</FlexItem>
						</>
					) }
					{ streamedText && ! error && isSeo && (
						<FlexItem>
							<Button
								variant="secondary"
								onClick={ onRegenerate }
								size="compact"
							>
								{ __( 'Regenerate', 'wp-ai-edit' ) }
							</Button>
						</FlexItem>
					) }
					<FlexItem>
						<Button
							variant="tertiary"
							onClick={ handleClose }
							size="compact"
						>
							{ __( 'Close', 'wp-ai-edit' ) }
						</Button>
					</FlexItem>
				</Flex>
			) }
		</div>
	);
}
