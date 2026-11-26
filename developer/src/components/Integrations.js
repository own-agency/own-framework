import {
	Card,
	CardBody,
	CardHeader,
	ExternalLink,
	Panel,
	PanelBody,
	TextControl,
} from '@wordpress/components';

import { Badge, SiteCell } from './common.js';

/**
 * Which key a site ends up using, in the same order as
 * ownframework_get_google_maps_api_key(): own → network → wp-config.php.
 *
 * @param {string}  siteKey    The site's own key (as typed).
 * @param {string}  networkKey The network key (as typed).
 * @param {boolean} constant   OWN_GOOGLE_MAPS_API_KEY is defined.
 * @return {Array} [ label, tone ].
 */
function source( siteKey, networkKey, constant ) {
	if ( siteKey.trim() ) {
		return [ 'Eigen sleutel', 'success' ];
	}
	if ( networkKey.trim() ) {
		return [ 'Netwerksleutel', 'success' ];
	}
	if ( constant ) {
		return [ 'wp-config.php', 'muted' ];
	}
	return [ 'Geen sleutel', 'warning' ];
}

function MapsHelp() {
	return (
		<Panel className="own-developer-help">
			<PanelBody
				title="Welke API's moeten aan in Google Cloud?"
				initialOpen={ false }
			>
				<p>
					Zet in de{ ' ' }
					<ExternalLink href="https://console.cloud.google.com/apis/library?filter=category:maps">
						Google Cloud Console
					</ExternalLink>{ ' ' }
					deze drie API&apos;s <strong>elk apart</strong> aan. De ene
					aanzetten geldt niet voor de andere:
				</p>
				<ul>
					<li>
						<strong>Maps JavaScript API</strong>: het blok
						&quot;Google Maps kaart&quot; en het kaartje in het
						adresveld.
					</li>
					<li>
						<strong>Geocoding API</strong>: een adres omzetten naar
						coördinaten en terug.
					</li>
					<li>
						<strong>Places API</strong>: het zoekvak in het
						adresveld. Kies de <strong>klassieke</strong>{ ' ' }
						&quot;Places API&quot;, niet &quot;Places API
						(New)&quot;. Met alleen de nieuwe blijft het zoekvak
						leeg.
					</li>
				</ul>
				<p>
					Zie je &quot;Google Maps Platform rejected your
					request&quot;, dan staat de bijbehorende API nog uit. Na het
					aanzetten kan het een paar minuten duren. Beperk de sleutel
					tot de domeinen van de sites: hij staat in de broncode van
					elke pagina.
				</p>
			</PanelBody>
		</Panel>
	);
}

/**
 * Integrations: the Google Maps API key, network-wide and per site.
 *
 * @param {Object}   props
 * @param {Object}   props.state        Server state.
 * @param {Object}   props.form         Editable values.
 * @param {boolean}  props.isMultisite  Network key and site column.
 * @param {Function} props.onNetworkKey Called with the new network key.
 * @param {Function} props.onSiteKey    ( siteId, key ).
 */
export default function Integrations( {
	state,
	form,
	isMultisite,
	onNetworkKey,
	onSiteKey,
} ) {
	return (
		<Card className="own-developer-card">
			<CardHeader>
				<h2>Koppelingen</h2>
			</CardHeader>
			<CardBody>
				<p className="own-developer-card__lead">
					Google Maps API-sleutel voor het adresveld (Algemeen →
					Contact) en het blok &quot;Google Maps kaart&quot;.
					{ isMultisite &&
						' Een eigen sleutel per site gaat voor de netwerksleutel.' }
				</p>
				<MapsHelp />
				{ isMultisite && (
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						className="own-developer-key"
						label="Netwerksleutel"
						help="Voor alle sites zonder eigen sleutel."
						value={ form.mapsNetworkKey }
						onChange={ onNetworkKey }
						placeholder="AIza…"
						autoComplete="off"
						spellCheck={ false }
					/>
				) }
			</CardBody>
			<div className="own-developer-table-wrap">
				<table className="widefat striped own-developer-table">
					<thead>
						<tr>
							<th scope="col">Site</th>
							<th scope="col">
								{ isMultisite ? 'Eigen sleutel' : 'Sleutel' }
							</th>
							<th scope="col">Gebruikt</th>
						</tr>
					</thead>
					<tbody>
						{ state.sites.map( ( site ) => {
							const value = form.sites[ site.id ].mapsKey;
							const [ label, tone ] = source(
								value,
								isMultisite ? form.mapsNetworkKey : '',
								state.mapsConstantKey
							);
							return (
								<tr key={ site.id }>
									<th scope="row">
										<SiteCell
											site={ site }
											isMultisite={ isMultisite }
											link={ false }
										/>
									</th>
									<td>
										<TextControl
											__next40pxDefaultSize
											__nextHasNoMarginBottom
											hideLabelFromVision
											className="own-developer-key"
											label={ `Google Maps API-sleutel van ${ site.name }` }
											value={ value }
											onChange={ ( key ) =>
												onSiteKey( site.id, key )
											}
											placeholder={
												isMultisite
													? 'Netwerksleutel gebruiken'
													: 'AIza…'
											}
											autoComplete="off"
											spellCheck={ false }
										/>
									</td>
									<td>
										<Badge tone={ tone }>{ label }</Badge>
									</td>
								</tr>
							);
						} ) }
					</tbody>
				</table>
			</div>
		</Card>
	);
}
