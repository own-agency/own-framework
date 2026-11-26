/**
 * Ontwikkelaarspagina (Netwerkbeheer → Ontwikkelaar, of Gereedschap →
 * Ontwikkelaar op een losse site). Alleen stabiele @wordpress/components:
 * geen __experimental*, zodat een WordPress-update de pagina niet breekt.
 */
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

import App from './App.js';

domReady( () => {
	const root = document.getElementById( 'own-developer-root' );
	if ( root ) {
		createRoot( root ).render( <App /> );
	}
} );
