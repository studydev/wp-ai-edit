import { useState } from '@wordpress/element';
import { MenuItem, MenuGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	edit as editIcon,
	plus as plusIcon,
	starFilled,
	page,
	cloud,
	check,
	formatBold,
	search,
} from '@wordpress/icons';
import CommandInput from './command-input';

const AI_ACTIONS = [
	{
		key: 'command',
		label: __( 'Your Command', 'wp-ai-edit' ),
		icon: editIcon,
		hasInput: true,
	},
	{
		key: 'write_more',
		label: __( 'Write More', 'wp-ai-edit' ),
		icon: plusIcon,
	},
	{
		key: 'my_style',
		label: __( 'My Style', 'wp-ai-edit' ),
		icon: starFilled,
	},
	{
		key: 'highlight',
		label: __( 'Highlight', 'wp-ai-edit' ),
		icon: formatBold,
	},
	{
		key: 'summarize',
		label: __( 'Summarize', 'wp-ai-edit' ),
		icon: page,
	},
	{
		key: 'analogy',
		label: __( 'Write Analogy', 'wp-ai-edit' ),
		icon: cloud,
	},
	{
		key: 'grammar',
		label: __( 'Fix Grammar', 'wp-ai-edit' ),
		icon: check,
	},
	{
		key: 'seo_helper',
		label: __( 'SEO Helper', 'wp-ai-edit' ),
		icon: search,
	},
];

export default function ActionPopover( { onAction } ) {
	const [ showCommandInput, setShowCommandInput ] = useState( false );

	const handleAction = ( action ) => {
		if ( action.hasInput ) {
			setShowCommandInput( true );
			return;
		}
		onAction( action.key );
	};

	const handleCommandSubmit = ( command ) => {
		onAction( 'command', command );
	};

	if ( showCommandInput ) {
		return (
			<div className="wp-ai-edit-action-popover">
				<CommandInput
					onSubmit={ handleCommandSubmit }
					onCancel={ () => setShowCommandInput( false ) }
				/>
			</div>
		);
	}

	return (
		<div className="wp-ai-edit-action-popover">
			<MenuGroup>
				{ AI_ACTIONS.map( ( action ) => (
					<MenuItem
						key={ action.key }
						icon={ action.icon }
						onClick={ () => handleAction( action ) }
						iconPosition="left"
					>
						{ action.label }
					</MenuItem>
				) ) }
			</MenuGroup>
		</div>
	);
}

export { AI_ACTIONS };
