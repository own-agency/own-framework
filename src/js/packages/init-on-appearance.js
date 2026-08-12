// Front-end HTML staat al klaar zodra scripts draaien, dus een simpele
// "querySelectorAll bij script-load" volstond daar altijd. In de block-
// editor's iframe (enqueue_block_editor_assets, zelfde main.js-bundle) klopt
// die aanname niet: SCF rendert een dynamic block's preview-HTML asynchroon
// ná het laden van dit script, en Gutenberg vervangt bij elke attribuut-
// wijziging de hele DOM-subtree van zo'n block — een element dat nog niet
// bestaat op het moment dat main.js draait, of dat later vervangen wordt,
// wordt door een eenmalige query domweg gemist. Deze helper init't daarom
// niet alleen wat er al staat, maar blijft ook nieuw verschijnende elementen
// (zelfde selector) opvangen zolang de pagina/editor open is.
export function initOnAppearance(selector, init) {
  const seen = new WeakSet();

  function initIfNew(el) {
    if (seen.has(el)) return;
    seen.add(el);
    init(el);
  }

  function scan(root) {
    root.querySelectorAll(selector).forEach(initIfNew);
  }

  scan(document);

  new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      for (const node of mutation.addedNodes) {
        if (node.nodeType !== Node.ELEMENT_NODE) continue;
        if (node.matches(selector)) initIfNew(node);
        scan(node);
      }
    }
  }).observe(document.body, { childList: true, subtree: true });
}
