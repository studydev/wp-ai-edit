import { createReduxStore, register } from '@wordpress/data';

const STORE_NAME = 'wp-ai-edit';

const DEFAULT_STATE = {
	isLoading: false,
	currentAction: null,
	streamedText: '',
	selectedText: '',
	error: null,
	showResult: false,
	lastRequest: null,
};

const actions = {
	setLoading( isLoading ) {
		return { type: 'SET_LOADING', isLoading };
	},
	setCurrentAction( action ) {
		return { type: 'SET_CURRENT_ACTION', action };
	},
	appendStreamedText( text ) {
		return { type: 'APPEND_STREAMED_TEXT', text };
	},
	setStreamedText( text ) {
		return { type: 'SET_STREAMED_TEXT', text };
	},
	setSelectedText( text ) {
		return { type: 'SET_SELECTED_TEXT', text };
	},
	setError( error ) {
		return { type: 'SET_ERROR', error };
	},
	setShowResult( show ) {
		return { type: 'SET_SHOW_RESULT', show };
	},
	setLastRequest( request ) {
		return { type: 'SET_LAST_REQUEST', request };
	},
	reset() {
		return { type: 'RESET' };
	},
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case 'SET_LOADING':
			return { ...state, isLoading: action.isLoading };
		case 'SET_CURRENT_ACTION':
			return { ...state, currentAction: action.action };
		case 'APPEND_STREAMED_TEXT':
			return { ...state, streamedText: state.streamedText + action.text };
		case 'SET_STREAMED_TEXT':
			return { ...state, streamedText: action.text };
		case 'SET_SELECTED_TEXT':
			return { ...state, selectedText: action.text };
		case 'SET_ERROR':
			return { ...state, error: action.error, isLoading: false };
		case 'SET_SHOW_RESULT':
			return { ...state, showResult: action.show };
		case 'SET_LAST_REQUEST':
			return { ...state, lastRequest: action.request };
		case 'RESET':
			return { ...DEFAULT_STATE };
		default:
			return state;
	}
};

const selectors = {
	isLoading: ( state ) => state.isLoading,
	getCurrentAction: ( state ) => state.currentAction,
	getStreamedText: ( state ) => state.streamedText,
	getSelectedText: ( state ) => state.selectedText,
	getError: ( state ) => state.error,
	getShowResult: ( state ) => state.showResult,
	getLastRequest: ( state ) => state.lastRequest,
};

const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
} );

register( store );

export { STORE_NAME };
export default store;
