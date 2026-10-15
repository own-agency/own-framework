<?php

defined('ABSPATH') || exit();

/**
 * Zijbalk navigatie — bedoeld voor een smalle zijbalk-kolom (geen
 * "supports.align", zelfde reden als acf/content-blok: dit block vult
 * gewoon de breedte van waar het in staat, geen eigen sectie). Twee bronnen
 * ("Bron", editor.js's SelectControl-vervanger — zie hieronder waarom dat
 * hier gewoon een ACF-veld is):
 *
 * - "pages": de paginaboom rond de HUIDIGE pagina. Eigen recursieve
 *   get_pages()-functie ($render_page_branch/$render_page_item) i.p.v.
 *   wp_list_pages(): elk item met eigen kinderen krijgt een LOS
 *   uitklapknopje (rechts, rond) naast de link zelf — twee losse klikdoelen
 *   (navigeren vs. uitklappen), wat wp_list_pages() se eigen <ul>/<li>-output
 *   niet per item kan onderscheiden zonder een eigen Walker_Page, en een
 *   Walker maakt de per-item accordion-status (zie hieronder) alleen maar
 *   lastiger te volgen dan een simpele recursieve functie. "Root pagina"
 *   (ACF post_object-veld) is optioneel: leeg laten laat dit block zelf de
 *   bovenste voorouder van de huidige pagina opzoeken (get_post_ancestors(),
 *   dezelfde functie als inc/functions/breadcrumbs.php's eigen
 *   ownframework_get_breadcrumb_items()) — zo werkt het block zonder enige
 *   configuratie zodra het ergens in een paginaboom staat. Een EXPLICIET
 *   gekozen root maakt het block ook bruikbaar in een gedeelde template
 *   (dezelfde sectieboom op elke pagina, ongeacht welke pagina 'm toont) —
 *   geen aparte "automatisch/handmatig"-schakelaar nodig, dit ene veld dekt
 *   beide gevallen.
 *
 *   De ROOT is zelf ook gewoon het eerste lijst-item ($render_page_item,
 *   level 0) — EXACT dezelfde rij-/uitklapvorm als elk kind eronder (alleen
 *   "font-semibold", leest als sectie-ingang), in plaats van een apart
 *   "altijd open, platte lijst, scheidingslijn erboven"-geval: één
 *   consistente inspring-/accordionlogica op elk niveau maakt de ouder/kind-
 *   relatie in de hele boom in één oogopslag duidelijk, zonder dat een lijn
 *   moet suggereren wat de inspringing zelf al zou moeten laten zien. De kop
 *   erboven (InnerBlocks, hieronder) blijft de plek voor een los sectielabel
 *   als "Over de school"; de root-rij daaronder is de daadwerkelijke
 *   rootpagina zelf, met zijn eigen titel en link.
 *
 *   Uitklapstatus per item is lokale Alpine x-data op de <li> zelf (niet een
 *   gedeelde store, zelfde reden als acf/faqs's eigen accordion-items: elk
 *   item hoort onafhankelijk open/dicht te kunnen). Een tak die de HUIDIGE
 *   pagina als (klein)kind bevat, staat bij het laden al open
 *   ($current_ancestors, dezelfde get_post_ancestors()-array als hierboven)
 *   — een editor hoeft nooit zelf uit te klappen om te zien waar hij is. De
 *   HUIDIGE pagina krijgt een eigen indicator — een gekleurde rand aan de
 *   linkerkant, een lichte tint van diezelfde kleur als achtergrond, en de
 *   linktekst zelf in die kleur (zie "accentColor" verderop) — i.p.v. alleen
 *   vet/donkerder, duidelijk genoeg als actieve-staat zonder een volledig
 *   gevulde pil te zijn.
 *
 *   Het uitklap-/inklaptransitie is dezelfde CSS-grid-truc (0fr → 1fr
 *   grid-template-rows) als acf/faqs's accordion — geen Alpine collapse-
 *   plugin nodig (niet geïnstalleerd, src/js/packages/alpine.js), zie dat
 *   block's eigen docblock voor de volle uitleg.
 *
 * - "anchors": elke core/heading op DEZE pagina met een HTML-anker
 *   (WordPress' eigen native "Geavanceerd" → "HTML-anker"-veld op ieder
 *   kop-block, geen eigen ACF-veld nodig) wordt een lijst-item, met dezelfde
 *   ".own-zijbalk-navigatie-row"-opmaak als de "pages"-bron (geen eigen
 *   uitklapknop — dit is altijd een platte lijst, geen boom). Een kop zonder
 *   anker verschijnt simpelweg niet — dat IS de opt-in, geen aparte aan/uit-
 *   instelling per kop nodig. parse_blocks(get_the_content()) i.p.v. een
 *   regex over de gerenderde HTML: leest de kop-tekst en het "anchor"-
 *   attribuut rechtstreeks uit de block-structuur, ook als de kop genest zit
 *   in een group/kolom. Geen "actieve staat" hier (in tegenstelling tot
 *   "pages"): welk anker "actief" is hangt af van scrollpositie, en dat
 *   bijhouden (IntersectionObserver e.d.) is een aparte, veel grotere
 *   uitbreiding dan waar dit block nu om vraagt.
 *
 * "Bron" (en de twee bijbehorende paginaboom-velden) staan als ACF-velden
 * i.p.v. block-attributes, met conditional_logic — zelfde afweging als
 * acf/faqs (zie dat block's editor.js voor de volle uitleg): meerdere
 * inhoudelijk verschillende velden die elkaar uitsluiten, dus laat ACF zelf
 * tonen/verbergen i.p.v. alle velden altijd te tonen met een instructions-
 * tekst erbij.
 *
 * De kop erboven is InnerBlocks (een los core/heading), geen plain "title"-
 * attribute meer — zelfde <InnerBlocks />-vervang-truc als acf/hero/acf/cta
 * (zie acf/hero's eigen docblock voor de volle uitleg), zodat een editor 'm
 * gewoon met de native kop-toolbar kan stylen (niveau, uitlijning, kleur,
 * lettergrootte) i.p.v. via een los tekstveld. "allowedBlocks" staat bewust
 * op alleen "core/heading" (geen paragraph/buttons zoals acf/hero/acf/cta):
 * dit is een korte titel boven een lijst, geen rijke intro. templateLock
 * "false" (niet "all"): een editor die geen titel wil kan de kop gewoon
 * verwijderen, zelfde als voorheen "Titel" leeg laten. De dunne lijn eronder
 * (border-b) is puur decoratief, geen los veld — precies zoals de
 * aangeleverde screenshot.
 *
 * Dit block heeft geen aparte "kaart binnen een sectie"-laag zoals acf/cta —
 * de <nav> hieronder IS de kaart, dus het native "Kleuren"-paneel
 * (supports.color, get_block_wrapper_attributes() hieronder) kleurt precies
 * het juiste element. Geen eigen "cardBackgroundColor"-attribute/paneel
 * nodig zoals acf/cta's kaart: dat bestaat daar omdat de kaart een sub-
 * element van een BREDERE section is die het native paneel niet apart kan
 * kleuren (CLAUDE.md Traps #7) — hier is er maar één element, dus dat
 * probleem doet zich niet voor. Zonder gekozen kleur valt de kaart terug op
 * een subtiele rand (src/css/components/zijbalk-navigatie.css, dezelfde
 * cijfers als acf/vacatures'/acf/agenda's eigen ".own-vacatures-surface"/
 * ".own-agenda-surface") zodat hij ook ongekleurd al als kaart afleest — geen
 * shadow ernaast: dat is in dit thema alleen voor een ZWEVEND paneel (bv.
 * blocks/header's eigen dropdown), een inline kaart in een kolom zoals deze
 * (net als de vacatures-/agenda-cards) blijft bij de rand alleen. De vulling
 * van het actieve item en de focusrand lezen
 * "accentColor" (editor.js's "Kleuren"-paneel, plain block-attribute
 * net als acf/vacatures' eigen "accentColor" — CLAUDE.md Traps #7, dit zijn
 * sub-elementen die het native "Kleuren"-paneel hierboven nooit bereikt).
 * Leeg blijft gewoon het thema se eigen primary-kleurtoken
 * (zijbalk-navigatie.css's "--own-zijbalk-navigatie-accent-fallback"), dus
 * zonder gekozen accent verandert er niets aan het bestaande gedrag. De
 * accentkleur zit alleen in de vulling van het actieve item, niet in de
 * tekst: zo blijft die op elke accentkleur leesbaar, ook een lichte.
 */

$source = get_field('zijbalk_navigatie_bron') ?: 'pages';
if (!in_array($source, ['pages', 'anchors'], true)) {
    $source = 'pages';
}

$accent = $block['accentColor'] ?? '';

$list_html = '';

if ($source === 'pages') {
    $current_id = (int) get_the_ID();
    $root_id = (int) (get_field('zijbalk_navigatie_root') ?: 0);

    if ($root_id <= 0) {
        $root_ancestors = get_post_ancestors($current_id);
        $root_id = $root_ancestors ? (int) end($root_ancestors) : $current_id;
    }

    $depth_limit = max(0, (int) (get_field('zijbalk_navigatie_diepte') ?: 0));
    $current_ancestors = get_post_ancestors($current_id);

    /**
     * Bouwt één niveau van de boom (de kinderen van $parent_id), elk via
     * $render_page_item. $level telt vanaf 1 (de kinderen van de root) —
     * "Diepte" (ACF-veld) stopt de recursie zodra $level daar voorbij gaat, 0
     * betekent "geen limiet".
     */
    $render_page_branch = function (int $parent_id, int $level) use (&$render_page_item, $depth_limit): string {
        if ($depth_limit > 0 && $level > $depth_limit) {
            return '';
        }

        $children = get_pages([
            'parent' => $parent_id,
            'post_status' => 'publish',
            'sort_column' => 'menu_order, post_title',
        ]);

        if (empty($children)) {
            return '';
        }

        $branch_html = '';
        foreach ($children as $page) {
            $branch_html .= $render_page_item((int) $page->ID, $level);
        }

        return $branch_html;
    };

    /**
     * Rendert één pagina als <li>, inclusief zijn eigen kinderen genest
     * eronder (via $render_page_branch) — dezelfde accordion-vorm op elk
     * niveau, de root (level 0) INCLUSIEF: geen apart "altijd open, platte
     * lijst, scheidingslijn"-geval meer voor de root. Zo is de ouder/kind-
     * relatie overal exact dezelfde uitklapbare inspringing (rechte lijn +
     * pl-3, zijbalk-navigatie.css), in plaats van een lijn die een tak
     * suggereert zonder dat de kinderen er ook echt onder inspringen. Enige
     * verschil op level 0: "font-semibold" (leest als sectie-ingang) en geen
     * eigen modifier-class nodig — dat ene Tailwind-utility-verschil verdient
     * geen aparte functie.
     */
    $render_page_item = function (int $page_id, int $level) use (&$render_page_branch, $current_id, $current_ancestors): string {
        $is_current = $page_id === $current_id;
        $nested_html = $render_page_branch($page_id, $level + 1);
        $has_children = $nested_html !== '';
        $is_open = $is_current || in_array($page_id, $current_ancestors, true);

        // De rij zelf heeft geen padding: die zit op de link ("py-2.5
        // px-3.5", zelfde maat als blocks/header's ".own-header-submenu__link"),
        // zodat het hele vak klikbaar is en niet alleen de tekst. Heeft het
        // item subpagina's, dan is de rechterkant van het vak over de volle
        // hoogte de uitklapknop — link en knop sluiten zonder dode zone op
        // elkaar aan, dus geen misklik tussen "openen" en "uitklappen".
        // Een class-attribute nooit op een cijfer laten eindigen (CLAUDE.md's
        // wptexturize-val), vandaar "truncate"/"rounded-xl" steeds achteraan.
        // Achtergrond/tekst van "--current" komen volledig uit CSS
        // (zijbalk-navigatie.css) via "--own-zijbalk-navigatie-accent".
        $row_classes = 'own-zijbalk-navigatie-row flex items-stretch rounded-xl' . ($level === 0 ? ' font-semibold' : '');
        if ($is_current) {
            $row_classes .= ' own-zijbalk-navigatie-row--current';
        }

        // esc_attr() ook rond deze x-data, ook al kan "true"/"false" zelf
        // nooit een aanhalingsteken bevatten — zelfde blanket-gewoonte als
        // CLAUDE.md's eigen Alpine-voorbeeld, voor als dat ooit verandert.
        $item_html = '<li class="own-zijbalk-navigatie-item"' . ($has_children ? ' x-data="' . esc_attr('{ open: ' . ($is_open ? 'true' : 'false') . ' }') . '"' : '') . '>';
        $item_html .= '<div class="' . esc_attr($row_classes) . '">';
        $title = get_the_title($page_id);
        $link_padding = $has_children ? 'py-2.5 pl-3.5 pr-3' : 'py-2.5 px-3.5';
        $item_html .= '<a href="' . esc_url((string) get_permalink($page_id)) . '" class="own-zijbalk-navigatie-link block flex-1 min-w-0 ' . $link_padding . ' truncate">' . esc_html($title) . '</a>';
        if ($has_children) {
            // Het hele rechterdeel van het vak (w-12 = 48px, volle hoogte) is
            // de knop; bij hover verschijnt een klein afgerond vlakje achter de
            // chevron (zijbalk-navigatie.css).
            $item_html .= '<button type="button" class="own-zijbalk-navigatie-toggle shrink-0 self-stretch w-12 flex items-center justify-center" @click="open = !open" :aria-expanded="open.toString()" aria-label="' . esc_attr(sprintf(__('Subpagina\'s van %s tonen of verbergen', 'own-framework'), $title)) . '">'
                . '<span class="own-zijbalk-navigatie-toggle-chevron inline-flex items-center justify-center w-8 h-8 rounded-lg">'
                . '<span class="own-zijbalk-navigatie-toggle-icon inline-flex" :class="{ \'rotate-180\': open }">' . ownframework_get_icon('chevron-down', 'w-4 h-4') . '</span>'
                . '</span>'
                . '</button>';
        }
        $item_html .= '</div>';
        if ($has_children) {
            $item_html .= '<div class="grid transition-[grid-template-rows] duration-300 ease-in-out" :class="open ? \'grid-rows-[1fr]\' : \'grid-rows-[0fr]\'">'
                . '<div class="overflow-hidden"><ul class="own-zijbalk-navigatie-children m-0 pl-3 list-none">' . $nested_html . '</ul></div></div>';
        }
        $item_html .= '</li>';

        return $item_html;
    };

    $list_html = $root_id > 0 ? $render_page_item($root_id, 0) : '';
} else {
    $headings = [];

    $collect_headings = function (array $blocks) use (&$collect_headings, &$headings) {
        foreach ($blocks as $inner_block) {
            if (($inner_block['blockName'] ?? '') === 'core/heading' && !empty($inner_block['attrs']['anchor'])) {
                $headings[] = [
                    'anchor' => $inner_block['attrs']['anchor'],
                    'level' => (int) ($inner_block['attrs']['level'] ?? 2),
                    'label' => trim(wp_strip_all_tags($inner_block['innerHTML'] ?? '')),
                ];
            }
            if (!empty($inner_block['innerBlocks'])) {
                $collect_headings($inner_block['innerBlocks']);
            }
        }
    };
    $collect_headings(parse_blocks((string) get_the_content()));

    foreach ($headings as $heading) {
        if ($heading['label'] === '') {
            continue;
        }
        // "--level-<cijfer>" NIET als laatste token: een class-attribute mag
        // van wptexturize() nooit op een cijfer eindigen (zie CLAUDE.md,
        // zelfde val als acf/hero/acf/proces), en dit cijfer komt
        // rechtstreeks uit $heading['level'] — dus de vaste klasse erna.
        $list_html .= '<li class="own-zijbalk-navigatie-anchor--level-' . (int) $heading['level'] . ' own-zijbalk-navigatie-item">'
            . '<div class="own-zijbalk-navigatie-row flex rounded-xl">'
            . '<a href="#' . esc_attr($heading['anchor']) . '" class="own-zijbalk-navigatie-link block flex-1 min-w-0 py-2.5 px-3.5 truncate">' . esc_html($heading['label']) . '</a>'
            . '</div></li>';
    }
}

$has_items = trim($list_html) !== '';

if (!$has_items && empty($is_preview)) {
    return;
}

$style_parts = [];
if ($accent !== '') {
    $style_parts[] = '--own-zijbalk-navigatie-accent:' . esc_attr($accent);
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'relative rounded-2xl p-4 sm:p-6 lg:pointer-fine:p-8 own-zijbalk-navigatie',
    'style' => implode(';', $style_parts),
]);

/**
 * Letterlijke placeholdertekst, geen React-component — zie acf/hero's eigen
 * docblock voor de volle uitleg.
 */
$heading_inner_blocks = '<InnerBlocks template=\'[["core/heading",{"level":3,"placeholder":"Titel…"}]]\' allowedBlocks=\'["core/heading"]\' templateLock=\'false\' />';
?>
<nav <?= $wrapper_attributes; ?> aria-label="<?php esc_attr_e('Zijbalk navigatie', 'own-framework'); ?>">
    <div class="own-zijbalk-navigatie-heading mb-4 pb-4 border-b border-current/10 [&_:is(h1,h2,h3,h4,h5,h6)]:mt-0!">
        <?= $heading_inner_blocks; ?>
    </div>

    <?php if ($has_items) : ?>
        <ul class="own-zijbalk-navigatie-list m-0 p-0 flex flex-col gap-0.5 list-none">
            <?= $list_html; ?>
        </ul>
    <?php else : ?>
        <p class="opacity-50 text-sm"><?php esc_html_e('Nog niets om te tonen — kies pagina\'s met subpagina\'s, of zet een HTML-anker op een kop.', 'own-framework'); ?></p>
    <?php endif; ?>
</nav>
