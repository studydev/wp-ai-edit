import { useState, useCallback } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import { BlockControls } from '@wordpress/block-editor';
import {
	ToolbarGroup,
	ToolbarButton,
	Dropdown,
	Popover,
} from '@wordpress/components';
import { useSelect, useDispatch, select } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

import ActionPopover from './action-popover';
import ResultPopover from './result-popover';
import { streamGenerate } from './streaming-handler';
import { STORE_NAME } from '../store';

const SUPPORTED_BLOCKS = [
	'core/paragraph',
	'core/heading',
	'core/list',
	'core/quote',
	'core/list-item',
];

const getEditableAttributeName = ( blockName ) => {
	switch ( blockName ) {
		case 'core/list':
			return 'values';
		case 'core/quote':
			return 'value';
		case 'core/paragraph':
		case 'core/heading':
		case 'core/list-item':
		default:
			return 'content';
	}
};

const stripHtml = ( html ) => {
	const el = document.createElement( 'div' );
	el.innerHTML = html;
	return el.textContent || el.innerText || '';
};

/**
 * Recursively collect plain text from all blocks in the editor.
 */
const collectAllBlocksText = () => {
	const allBlocks = select( 'core/block-editor' ).getBlocks();
	const gather = ( blocks ) => {
		const texts = [];
		for ( const block of blocks ) {
			if ( SUPPORTED_BLOCKS.includes( block.name ) ) {
				const attr = getEditableAttributeName( block.name );
				const c = block.attributes[ attr ] || '';
				const t = typeof c === 'string' ? stripHtml( c ) : String( c );
				if ( t ) {
					texts.push( t );
				}
			}
			if ( block.innerBlocks?.length > 0 ) {
				texts.push( ...gather( block.innerBlocks ) );
			}
		}
		return texts;
	};
	return gather( allBlocks ).join( '\n\n' );
};

const AI_ICON_URL =
	'https://hemtory.com/wp-content/uploads/2026/04/cropped-hemtory-1.png';

const AiIcon = () => (
	<img
		src={ AI_ICON_URL }
		alt="AI"
		width={ 24 }
		height={ 24 }
		style={ { borderRadius: '50%', display: 'block' } }
	/>
);

const withAIEditControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { name, clientId, attributes, isSelected } = props;

		// eslint-disable-next-line react-hooks/rules-of-hooks
		const isPartOfMultiSelection = useSelect(
			( sel ) => {
				const ids =
					sel( 'core/block-editor' ).getMultiSelectedBlockClientIds();
				return ids.length > 0 && ids.includes( clientId );
			},
			[ clientId ]
		);

		// eslint-disable-next-line react-hooks/rules-of-hooks
		const isFirstInMulti = useSelect(
			( sel ) => {
				const ids =
					sel( 'core/block-editor' ).getMultiSelectedBlockClientIds();
				return ids.length > 0 && ids[ 0 ] === clientId;
			},
			[ clientId ]
		);

		const shouldShow =
			SUPPORTED_BLOCKS.includes( name ) &&
			( isSelected || isFirstInMulti );

		if ( ! shouldShow ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<>
				<AIEditControls
					clientId={ clientId }
					blockName={ name }
					attributes={ attributes }
					isMulti={ isPartOfMultiSelection }
				/>
				<BlockEdit { ...props } />
			</>
		);
	};
}, 'withAIEditControls' );

function AIEditControls( { clientId, blockName, attributes, isMulti } ) {
	const [ showResult, setShowResult ] = useState( false );
	const [ resultAnchor, setResultAnchor ] = useState( null );

	const multiBlocks = useSelect(
		( sel ) => {
			if ( ! isMulti ) {
				return [];
			}
			const ids =
				sel( 'core/block-editor' ).getMultiSelectedBlockClientIds();
			return ids
				.map( ( id ) => {
					const block = sel( 'core/block-editor' ).getBlock( id );
					return block
						? {
								clientId: id,
								name: block.name,
								attributes: block.attributes,
						  }
						: null;
				} )
				.filter( Boolean );
		},
		[ isMulti ]
	);

	const {
		setLoading,
		setCurrentAction,
		appendStreamedText,
		setError,
		setLastRequest,
		reset,
	} = useDispatch( STORE_NAME );

	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	const getBlockText = useCallback( () => {
		if ( isMulti && multiBlocks.length > 0 ) {
			return multiBlocks
				.map( ( b ) => {
					const attr = getEditableAttributeName( b.name );
					const c = b.attributes[ attr ] || '';
					return typeof c === 'string' ? stripHtml( c ) : String( c );
				} )
				.filter( Boolean )
				.join( '\n\n' );
		}
		const attr = getEditableAttributeName( blockName );
		const content = attributes[ attr ] || '';
		return typeof content === 'string'
			? stripHtml( content )
			: String( content );
	}, [ attributes, blockName, isMulti, multiBlocks ] );

	const executeAction = useCallback(
		async ( action, command = '', sourceText = '' ) => {
			let text;
			if ( action === 'seo_helper' ) {
				text = collectAllBlocksText();
			} else {
				text = sourceText || getBlockText();
			}

			if ( ! text && action !== 'command' ) {
				setError(
					__( 'No text content in the selected block.', 'wp-ai-edit' )
				);
				setShowResult( true );
				return;
			}

			const requestParams = { action, text, command };

			reset();
			setCurrentAction( action );
			setLoading( true );
			setLastRequest( requestParams );
			setShowResult( true );

			await streamGenerate( requestParams, {
				onChunk: ( chunk ) => {
					appendStreamedText( chunk );
				},
				onDone: () => {
					setLoading( false );
				},
				onError: ( errorMsg ) => {
					setError( errorMsg );
					setLoading( false );
				},
			} );
		},
		[
			getBlockText,
			reset,
			setCurrentAction,
			setLoading,
			setLastRequest,
			appendStreamedText,
			setError,
		]
	);

	const handleUse = useCallback( () => {
		const store = select( STORE_NAME );
		const generatedText = store.getStreamedText();

		if ( ! generatedText ) {
			return;
		}

		const currentAction = store.getCurrentAction();

		if ( isMulti && multiBlocks.length > 0 ) {
			// For multi-block: put result in first block, clear the rest
			const firstAttr = getEditableAttributeName( multiBlocks[ 0 ].name );
			const firstContent = multiBlocks[ 0 ].attributes[ firstAttr ] || '';
			const next =
				currentAction === 'write_more'
					? [ firstContent, generatedText ]
							.filter( Boolean )
							.join( '\n\n' )
					: generatedText;
			updateBlockAttributes( multiBlocks[ 0 ].clientId, {
				[ firstAttr ]: next,
			} );
			for ( let i = 1; i < multiBlocks.length; i++ ) {
				const a = getEditableAttributeName( multiBlocks[ i ].name );
				updateBlockAttributes( multiBlocks[ i ].clientId, {
					[ a ]: '',
				} );
			}
		} else {
			const attr = getEditableAttributeName( blockName );
			const current = attributes[ attr ] || '';
			const next =
				currentAction === 'write_more'
					? [ current, generatedText ]
							.filter( Boolean )
							.join( '\n\n' )
					: generatedText;
			updateBlockAttributes( clientId, { [ attr ]: next } );
		}

		reset();
		setShowResult( false );
	}, [
		attributes,
		blockName,
		clientId,
		isMulti,
		multiBlocks,
		reset,
		updateBlockAttributes,
	] );

	const handleRegenerate = useCallback( () => {
		const store = select( STORE_NAME );
		const last = store.getLastRequest();
		if ( last ) {
			executeAction( last.action, last.command, last.text );
		}
	}, [ executeAction ] );

	const handleWriteMore = useCallback( () => {
		const store = select( STORE_NAME );
		const text = store.getStreamedText();
		if ( text ) {
			executeAction( 'write_more', '', text );
		}
	}, [ executeAction ] );

	const handleCloseResult = useCallback( () => {
		reset();
		setShowResult( false );
	}, [ reset ] );

	return (
		<BlockControls>
			<ToolbarGroup>
				<Dropdown
					popoverProps={ {
						placement: 'bottom-start',
						offset: 8,
						className: 'wp-ai-edit-popover',
					} }
					renderToggle={ ( { isOpen, onToggle } ) => (
						<ToolbarButton
							icon={ AiIcon }
							label={ __( 'AI Edit', 'wp-ai-edit' ) }
							onClick={ ( e ) => {
								if ( ! showResult ) {
									setResultAnchor( e.currentTarget );
									onToggle();
								}
							} }
							isActive={ isOpen || showResult }
							className="wp-ai-edit-toolbar-button"
						/>
					) }
					renderContent={ ( { onClose } ) => (
						<ActionPopover
							onAction={ ( action, command ) => {
								onClose();
								executeAction( action, command );
							} }
						/>
					) }
				/>
			</ToolbarGroup>

			{ showResult && resultAnchor && (
				<Popover
					anchor={ resultAnchor }
					placement="bottom-start"
					onFocusOutside={ () => {} }
					offset={ 8 }
					className="wp-ai-edit-popover wp-ai-edit-result-popover-wrapper"
				>
					<ResultPopover
						onUse={ handleUse }
						onRegenerate={ handleRegenerate }
						onWriteMore={ handleWriteMore }
						onClose={ handleCloseResult }
						isSeo={
							select( STORE_NAME ).getCurrentAction() ===
							'seo_helper'
						}
					/>
				</Popover>
			) }
		</BlockControls>
	);
}

// Register the block editor extension
addFilter(
	'editor.BlockEdit',
	'wp-ai-edit/with-ai-controls',
	withAIEditControls
);
