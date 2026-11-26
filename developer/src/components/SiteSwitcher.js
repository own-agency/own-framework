import {
	Card,
	CardBody,
	CardHeader,
	ToggleControl,
} from '@wordpress/components';

/**
 * Multisite only: network-wide switch for acf/footer-topbar's site switcher.
 *
 * @param {Object}   props
 * @param {boolean}  props.value    Show non-public sites.
 * @param {Function} props.onChange Called with the new value.
 */
export default function SiteSwitcher( { value, onChange } ) {
	return (
		<Card className="own-developer-card">
			<CardHeader>
				<h2>Site-wisselaar</h2>
			</CardHeader>
			<CardBody>
				<p className="own-developer-card__lead">
					De wisselaar in de footer-topbalk toont normaal alleen sites
					die in Netwerkbeheer → Sites als &quot;Public&quot; staan.
				</p>
				<ToggleControl
					__nextHasNoMarginBottom
					label="Toon ook niet-publieke sites"
					help="Geldt voor alle sites in het netwerk. Zet dit uit zodra de sites live gaan."
					checked={ value }
					onChange={ onChange }
				/>
			</CardBody>
		</Card>
	);
}
