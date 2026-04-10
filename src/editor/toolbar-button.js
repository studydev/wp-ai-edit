import { ToolbarButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * AI sparkle icon SVG.
 */
const AiIcon = () => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		width="24"
		height="24"
		fill="currentColor"
	>
		<path d="M12 2L14.09 8.26L20 9.27L15.55 13.97L16.91 20L12 16.9L7.09 20L8.45 13.97L4 9.27L9.91 8.26L12 2Z" />
	</svg>
);

export default function AIToolbarButton( { onClick, isActive } ) {
	return (
		<ToolbarButton
			icon={ AiIcon }
			label={ __( 'AI Edit', 'wp-ai-edit' ) }
			onClick={ onClick }
			isActive={ isActive }
			className="wp-ai-edit-toolbar-button"
		/>
	);
}
