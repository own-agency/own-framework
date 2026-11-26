import apiFetch from '@wordpress/api-fetch';
import { Button, Notice, Spinner } from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';

import ContentTypes from './components/ContentTypes.js';
import Integrations from './components/Integrations.js';
import SiteSwitcher from './components/SiteSwitcher.js';

const PATH = '/own-framework/v1/developer';
const { isMultisite, version } = window.ownDeveloper || {};

/**
 * What can be changed on the page, taken from the server state. Kept apart
 * from the state itself so "unsaved changes" is a plain comparison.
 *
 * @param {Object} state Server state.
 * @return {Object} Editable values.
 */
function toForm( state ) {
	return {
		switcherNonPublic: state.switcherNonPublic,
		mapsNetworkKey: state.mapsNetworkKey,
		sites: Object.fromEntries(
			state.sites.map( ( site ) => [
				site.id,
				{
					postTypes: Object.fromEntries(
						Object.entries( site.postTypes ).map(
							( [ slug, value ] ) => [ slug, value.enabled ]
						)
					),
					mapsKey: site.mapsKey,
				},
			] )
		),
	};
}

export default function App() {
	const [ state, setState ] = useState( null );
	const [ form, setForm ] = useState( null );
	const [ saving, setSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		apiFetch( { path: PATH } )
			.then( ( data ) => {
				setState( data );
				setForm( toForm( data ) );
			} )
			.catch( ( error ) =>
				setNotice( { status: 'error', text: error.message } )
			);
	}, [] );

	const dirty = useMemo(
		() =>
			!! state &&
			JSON.stringify( form ) !== JSON.stringify( toForm( state ) ),
		[ state, form ]
	);

	// Leaving with unsaved changes asks first, like the post editor.
	useEffect( () => {
		if ( ! dirty ) {
			return;
		}
		const warn = ( event ) => {
			event.preventDefault();
			event.returnValue = '';
		};
		window.addEventListener( 'beforeunload', warn );
		return () => window.removeEventListener( 'beforeunload', warn );
	}, [ dirty ] );

	const setSite = ( id, changes ) =>
		setForm( ( current ) => ( {
			...current,
			sites: {
				...current.sites,
				[ id ]: { ...current.sites[ id ], ...changes },
			},
		} ) );

	const save = () => {
		setSaving( true );
		setNotice( null );
		apiFetch( {
			path: PATH,
			method: 'POST',
			data: {
				...( isMultisite && {
					switcherNonPublic: form.switcherNonPublic,
					mapsNetworkKey: form.mapsNetworkKey,
				} ),
				sites: Object.entries( form.sites ).map( ( [ id, site ] ) => ( {
					id: Number( id ),
					...site,
				} ) ),
			},
		} )
			.then( ( data ) => {
				setState( data );
				setForm( toForm( data ) );
				setNotice( {
					status: 'success',
					text: 'Instellingen opgeslagen.',
				} );
			} )
			.catch( ( error ) =>
				setNotice( { status: 'error', text: error.message } )
			)
			.finally( () => setSaving( false ) );
	};

	return (
		<>
			<header className="own-developer__header">
				<h1>
					Ontwikkelaar
					<span className="own-developer-badge">
						Alleen voor het ontwikkelteam
					</span>
				</h1>
				<p className="own-developer__lead">
					{ isMultisite
						? 'Instellingen die klantbeheerders niet zien. Contenttypes en sleutels stel je hier per site in.'
						: 'Instellingen die klantbeheerders niet zien.' }
					{ version && (
						<span className="own-developer__version">
							OWN Framework { version }
						</span>
					) }
				</p>
			</header>

			{ notice && (
				<Notice
					className="own-developer__notice"
					status={ notice.status }
					onRemove={ () => setNotice( null ) }
				>
					{ notice.text }
				</Notice>
			) }

			{ ! state && ! notice && <Spinner /> }

			{ state && form && (
				<div className="own-developer__stack">
					{ isMultisite && (
						<SiteSwitcher
							value={ form.switcherNonPublic }
							onChange={ ( switcherNonPublic ) =>
								setForm( { ...form, switcherNonPublic } )
							}
						/>
					) }
					<ContentTypes
						state={ state }
						form={ form }
						isMultisite={ isMultisite }
						onToggle={ ( id, slug, enabled ) =>
							setSite( id, {
								postTypes: {
									...form.sites[ id ].postTypes,
									[ slug ]: enabled,
								},
							} )
						}
					/>
					<Integrations
						state={ state }
						form={ form }
						isMultisite={ isMultisite }
						onNetworkKey={ ( mapsNetworkKey ) =>
							setForm( { ...form, mapsNetworkKey } )
						}
						onSiteKey={ ( id, mapsKey ) =>
							setSite( id, { mapsKey } )
						}
					/>

					<div className="own-developer__savebar">
						<Button
							variant="primary"
							isBusy={ saving }
							disabled={ saving || ! dirty }
							onClick={ save }
						>
							Wijzigingen opslaan
						</Button>
						{ dirty && (
							<span className="own-developer__unsaved">
								Niet-opgeslagen wijzigingen
							</span>
						) }
					</div>
				</div>
			) }
		</>
	);
}
