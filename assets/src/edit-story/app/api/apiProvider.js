/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { useConfig } from '../';
import Context from './context';

function APIProvider( { children } ) {
	const { api: { stories, media } } = useConfig();

	const getStoryById = useCallback(
		( storyId ) => {
			const path = addQueryArgs( `${ stories }/${ storyId }`, { context: `edit` } );
			return apiFetch( { path } );
		},
		[ stories ],
	);

	const saveStoryById = useCallback(
		/**
		 * Fire REST API call to save story.
		 *
		 * @param {number}   storyId Story post id.
		 * @param {string}   title Story title.
		 * @param {string}   status Post status, draft or published.
		 * @param {Array}    pages Array of all pages.
		 * @param {number}   author User ID of story author.
		 * @param {string}   slug   The slug of the story.
		 * @return {Promise} Return apiFetch promise.
		 */
		( storyId, title, status, pages, author, slug, content ) => {
			return apiFetch( {
				path: `${ stories }/${ storyId }`,
				data: {
					title,
					status,
					author,
					slug,
					content,
					story_data: pages,
				},
				method: 'POST',
			} );
		},
		[ stories ],
	);

	const getMedia = useCallback(
		( { mediaType, searchTerm } ) => {
			let apiPath = media;
			const perPage = 100;
			apiPath = addQueryArgs( apiPath, { per_page: perPage } );

			if ( mediaType ) {
				apiPath = addQueryArgs( apiPath, { media_type: mediaType } );
			}

			if ( searchTerm ) {
				apiPath = addQueryArgs( apiPath, { search: searchTerm } );
			}

			return apiFetch( { path: apiPath } )
				.then( ( data ) => data.map(
					( {
						guid: { rendered: src },
						media_details: { width: oWidth, height: oHeight },
						mime_type: mimeType,
					} ) => ( {
						src,
						oWidth,
						oHeight,
						mimeType,
					} ),
				) );
		},	[ media ],
	);

	const state = {
		actions: {
			getStoryById,
			getMedia,
			saveStoryById,
		},
	};

	return (
		<Context.Provider value={ state }>
			{ children }
		</Context.Provider>
	);
}

APIProvider.propTypes = {
	children: PropTypes.oneOfType( [
		PropTypes.arrayOf( PropTypes.node ),
		PropTypes.node,
	] ).isRequired,
};

export default APIProvider;
