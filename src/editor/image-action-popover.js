import { useState } from '@wordpress/element';
import { MenuItem, MenuGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	edit as editIcon,
	image as imageIcon,
	title as titleIcon,
} from '@wordpress/icons';
import CommandInput from './command-input';

const IMAGE_ACTIONS = [
	{
		key: 'image_command',
		label: __( 'Your Command', 'wp-ai-edit' ),
		icon: editIcon,
		hasInput: true,
	},
	{
		key: 'describe_image',
		label: __( 'Describe Image', 'wp-ai-edit' ),
		icon: imageIcon,
	},
	{
		key: 'suggest_caption',
		label: __( 'Suggest Caption', 'wp-ai-edit' ),
		icon: titleIcon,
	},
];

export default function ImageActionPopover( { onAction } ) {
	const [ showCommandInput, setShowCommandInput ] = useState( false );
	const providerSummary = window.wpAiEdit?.activeProviderSummary || '';

	const handleAction = ( action ) => {
		if ( action.hasInput ) {
			setShowCommandInput( true );
			return;
		}
		onAction( action.key );
	};

	const handleCommandSubmit = ( command ) => {
		onAction( 'image_command', command );
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
				{ IMAGE_ACTIONS.map( ( action ) => (
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
			{ providerSummary && (
				<div className="wp-ai-edit-provider-info">
					<small>{ providerSummary }</small>
				</div>
			) }
		</div>
	);
}
