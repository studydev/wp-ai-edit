import { useState } from '@wordpress/element';
import { TextControl, Button, Flex, FlexItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function CommandInput( { onSubmit, onCancel } ) {
	const [ command, setCommand ] = useState( '' );

	const handleSubmit = () => {
		if ( command.trim() ) {
			onSubmit( command.trim() );
		}
	};

	const handleKeyDown = ( e ) => {
		if ( e.key === 'Enter' && ! e.shiftKey ) {
			e.preventDefault();
			handleSubmit();
		}
		if ( e.key === 'Escape' ) {
			onCancel();
		}
	};

	return (
		<div className="wp-ai-edit-command-input">
			<TextControl
				label={ __( 'Enter your command', 'wp-ai-edit' ) }
				value={ command }
				onChange={ setCommand }
				onKeyDown={ handleKeyDown }
				placeholder={ __(
					'e.g., Rewrite this in a formal tone',
					'wp-ai-edit'
				) }
			/>
			<Flex justify="flex-end">
				<FlexItem>
					<Button
						variant="tertiary"
						onClick={ onCancel }
						size="compact"
					>
						{ __( 'Cancel', 'wp-ai-edit' ) }
					</Button>
				</FlexItem>
				<FlexItem>
					<Button
						variant="primary"
						onClick={ handleSubmit }
						disabled={ ! command.trim() }
						size="compact"
					>
						{ __( 'Run', 'wp-ai-edit' ) }
					</Button>
				</FlexItem>
			</Flex>
		</div>
	);
}
