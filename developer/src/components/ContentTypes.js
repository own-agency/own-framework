import {
	Card,
	CardBody,
	CardHeader,
	FormToggle,
	Notice,
} from '@wordpress/components';

import { SiteCell } from './common.js';

/**
 * Content types per site: one row per site, one toggle per post type.
 *
 * @param {Object}   props
 * @param {Object}   props.state       Server state (labels, counts, sites).
 * @param {Object}   props.form        Editable values.
 * @param {boolean}  props.isMultisite Show the site column and badges.
 * @param {Function} props.onToggle    ( siteId, slug, enabled ).
 */
export default function ContentTypes( { state, form, isMultisite, onToggle } ) {
	return (
		<Card className="own-developer-card">
			<CardHeader>
				<h2>Contenttypes{ isMultisite ? ' per site' : '' }</h2>
			</CardHeader>
			<CardBody>
				<p className="own-developer-card__lead">
					Zet een contenttype uit als een site het niet gebruikt.
				</p>
				<Notice status="info" isDismissible={ false }>
					<strong>Wat uitzetten doet.</strong> Het type verdwijnt uit
					het beheer en niemand kan de items nog bewerken, ook
					beheerders niet. Detail- en archiefpagina&apos;s geven een
					404, de bijbehorende blocks verdwijnen uit de inserter en
					tonen niets meer op pagina&apos;s. Bestaande items blijven
					bewaard: weer aanzetten zet alles terug.
				</Notice>
			</CardBody>
			<div className="own-developer-table-wrap">
				<table className="widefat striped own-developer-table">
					<thead>
						<tr>
							<th scope="col">Site</th>
							{ state.postTypes.map( ( type ) => (
								<th scope="col" key={ type.slug }>
									{ type.label }
								</th>
							) ) }
						</tr>
					</thead>
					<tbody>
						{ state.sites.map( ( site ) => (
							<tr key={ site.id }>
								<th scope="row">
									<SiteCell
										site={ site }
										isMultisite={ isMultisite }
									/>
								</th>
								{ state.postTypes.map( ( type ) => {
									const enabled =
										form.sites[ site.id ].postTypes[
											type.slug
										];
									const { count, enabled: saved } =
										site.postTypes[ type.slug ];
									const id = `own-pt-${ site.id }-${ type.slug }`;
									return (
										<td key={ type.slug }>
											<div className="own-developer-toggle">
												<FormToggle
													id={ id }
													checked={ enabled }
													aria-label={ `${ type.label } op ${ site.name }` }
													onChange={ () =>
														onToggle(
															site.id,
															type.slug,
															! enabled
														)
													}
												/>
												<label
													htmlFor={ id }
													className="own-developer-toggle__count"
												>
													{ count
														? `${ count } ${
																count === 1
																	? 'item'
																	: 'items'
														  }`
														: 'Leeg' }
												</label>
											</div>
											{ ! enabled && count > 0 && (
												<span className="own-developer-toggle__warning">
													{ saved
														? 'Wordt onzichtbaar'
														: 'Onzichtbaar' }
												</span>
											) }
										</td>
									);
								} ) }
							</tr>
						) ) }
					</tbody>
				</table>
			</div>
		</Card>
	);
}
