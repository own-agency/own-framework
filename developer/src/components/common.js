/**
 * Small pieces shared by the cards.
 */

/**
 * @param {Object} props
 * @param {string} props.tone     '' | 'warning' | 'muted' | 'success'.
 * @param {*}      props.children Label.
 */
export function Badge( { tone = '', children } ) {
	return (
		<span
			className={ `own-developer-badge${ tone ? ` is-${ tone }` : '' }` }
		>
			{ children }
		</span>
	);
}

/**
 * Site name (linked to its dashboard), address and status badges.
 *
 * @param {Object}  props
 * @param {Object}  props.site        Site from the REST state.
 * @param {boolean} props.isMultisite Show network badges.
 * @param {boolean} props.link        Link the name to the site's dashboard.
 */
export function SiteCell( { site, isMultisite, link = true } ) {
	const name = site.name || site.url;
	return (
		<div className="own-developer-site">
			{ link ? (
				<a className="own-developer-site__name" href={ site.adminUrl }>
					{ name }
				</a>
			) : (
				<span className="own-developer-site__name">{ name }</span>
			) }
			<span className="own-developer-site__url">
				{ site.url.replace( /^https?:\/\//, '' ).replace( /\/$/, '' ) }
			</span>
			{ link && (
				<span className="own-developer-site__badges">
					{ isMultisite && site.isMain && <Badge>Hoofdsite</Badge> }
					{ isMultisite && ! site.isPublic && (
						<Badge tone="warning">Niet publiek</Badge>
					) }
					{ ! site.ownTheme && (
						<Badge tone="muted">Ander thema</Badge>
					) }
				</span>
			) }
		</div>
	);
}
