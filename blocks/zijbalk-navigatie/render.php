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
 *   get_pages()-functie ($render_page_branch) i.p.v. wp_list_pages(): elk
 *   item met eigen kinderen krijgt een LOS uitklapknopje (rechts, rond) naast
 *   de link zelf — twee losse klikdoelen (navigeren vs. uitklappen), wat
 *   wp_list_pages() se eigen <ul>/<li>-output niet per item kan onderscheiden
 *   zonder een eigen Walker_Page, en een Walker maakt de per-item accordion-
 *   status (zie hieronder) alleen maar lastiger te volgen dan een simpele
 *   recursieve functie. "Root pagina" (ACF post_object-veld) is optioneel:
 *   leeg laten laat dit block zelf de bovenste voorouder van de huidige
 *   pagina opzoeken (get_post_ancestors(), dezelfde functie als inc/
 *   functions/breadcrumbs.php's eigen ownframework_get_breadcrumb_items())
 *   — zo werkt het block zonder enige configuratie zodra het ergens in een
 *   paginaboom staat. Een EXPLICIET gekozen root maakt het block ook
 *   bruikbaar in een gedeelde template (dezelfde sectieboom op elke pagina,
 *   ongeacht welke pagina 'm toont) — geen aparte "automatisch/handmatig"-
 *   schakelaar nodig, dit ene veld dekt beide gevallen. De ROOT zelf
 *   verschijnt bewust NIET als eigen lijst-item — alleen zijn kinderen: de
 *   kop erboven (InnerBlocks, hieronder) is de plek voor een sectienaam als
 *   "Over de school", de lijst toont de daadwerkelijke navigatie ernaartoe.
 *
 *   Uitklapstatus per item is lokale Alpine x-data op de <li> zelf (niet een
 *   gedeelde store, zelfde reden als acf/faqs's eigen accordion-items: elk
 *   item hoort onafhankelijk open/dicht te kunnen). Een tak die de HUIDIGE
 *   pagina als (klein)kind bevat, staat bij het laden al open
 *   ($current_ancestors, dezelfde get_post_ancestors()-array als hierboven)
 *   — een editor hoeft nooit zelf uit te klappen om te zien waar hij is. De
 *   HUIDIGE pagina zelf krijgt een volle rij als "pil" (achtergrondkleur
 *   var(--wp--preset--color--primary), witte tekst) i.p.v. alleen vet/
 *   donkerder — zelfde soort duidelijke actieve-staat als de screenshot die
 *   voor dit block is aangeleverd, alleen dan met dit theme's eigen
 *   kleurtoken i.p.v. een vaste hexkleur.
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
 * "sticky" (block-attribute, wél gewoon in de zijbalk zelf omdat het geen
 * databron is maar een simpele aan/uit-schakelaar) hergebruikt bewust
 * "sticky top-24", exact dezelfde klasse als acf/vacature-apply's eigen
 * kaart — dezelfde afstand tot een sticky header, geen nieuw getal nodig.
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
 * een subtiele rand/schaduw (src/css/components/zijbalk-navigatie.css,
 * dezelfde cijfers als acf/vacatures' eigen ".own-vacatures-surface") zodat
 * hij ook ongekleurd al als kaart afleest. De actieve-pagina-pil en het
 * ronde uitklapknopje gebruiken bewust GEEN eigen kleur-attributes (in
 * tegenstelling tot bv. acf/vacatures' cardTitleColor/cardLinkColor/etc.):
 * dit is één vast "actieve staat"-accent (het thema se eigen
 * primary-kleurtoken), geen per-instantie keuze — een derde kleurpaneel
 * zou hier alleen maar keuzestress toevoegen zonder een echt sub-element-
 * probleem op te lossen zoals bij acf/cta's kaart.
 */

$is_sticky = ($block['sticky'] ?? true) !== false;

$source = get_field('zijbalk_navigatie_bron') ?: 'pages';
if (!in_array($source, ['pages', 'anchors'], true)) {
    $source = 'pages';
}

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
     * Bouwt één niveau van de boom (de kinderen van $parent_id) en roept
     * zichzelf aan voor elk kind met eigen kinderen. $level telt vanaf 1 (de
     * kinderen van de root) — "Diepte" (ACF-veld) stopt de recursie zodra
     * $level daar voorbij gaat, 0 betekent "geen limiet".
     */
    $render_page_branch = function (int $parent_id, int $level) use (&$render_page_branch, $current_id, $current_ancestors, $depth_limit): string {
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
            $page_id = (int) $page->ID;
            $is_current = $page_id === $current_id;
            $nested_html = $render_page_branch($page_id, $level + 1);
            $has_children = $nested_html !== '';
            $is_open = $is_current || in_array($page_id, $current_ancestors, true);

            // "py-2.5"/"rounded-xl" nooit als laatste token: zie de docblock
            // hierboven en CLAUDE.md's wptexturize-val — vandaar "rounded-xl"
            // (eindigt op een letter) altijd achteraan.
            $row_classes = 'own-zijbalk-navigatie-row flex items-center justify-between gap-2 py-2.5 px-3 rounded-xl';
            $row_attr = '';
            if ($is_current) {
                $row_classes .= ' own-zijbalk-navigatie-row--current';
                $row_attr = ' style="background-color:var(--wp--preset--color--primary);color:#fff;"';
            }

            // esc_attr() ook rond deze x-data, ook al kan "true"/"false" zelf
            // nooit een aanhalingsteken bevatten — zelfde blanket-gewoonte als
            // CLAUDE.md's eigen Alpine-voorbeeld, voor als dat ooit verandert.
            $branch_html .= '<li class="own-zijbalk-navigatie-item"' . ($has_children ? ' x-data="' . esc_attr('{ open: ' . ($is_open ? 'true' : 'false') . ' }') . '"' : '') . '>';
            $branch_html .= '<div class="' . esc_attr($row_classes) . '"' . $row_attr . '>';
            $branch_html .= '<a href="' . esc_url((string) get_permalink($page_id)) . '" class="own-zijbalk-navigatie-link min-w-0 truncate">' . esc_html(get_the_title($page_id)) . '</a>';
            if ($has_children) {
                $branch_html .= '<button type="button" class="own-zijbalk-navigatie-toggle shrink-0 inline-flex items-center justify-center rounded-full w-8 h-8 bg-white" @click="open = !open" :aria-expanded="open.toString()">'
                    . '<span class="transition-transform duration-200 inline-flex" :class="{ \'rotate-180\': open }">' . ownframework_get_icon('chevron-down', 'w-3.5 h-3.5 text-gray-500') . '</span>'
                    . '</button>';
            }
            $branch_html .= '</div>';
            if ($has_children) {
                $branch_html .= '<div class="grid transition-[grid-template-rows] duration-300 ease-in-out" :class="open ? \'grid-rows-[1fr]\' : \'grid-rows-[0fr]\'">'
                    . '<div class="overflow-hidden"><ul class="own-zijbalk-navigatie-children m-0 pt-1 pb-1 pl-4 list-none">' . $nested_html . '</ul></div></div>';
            }
            $branch_html .= '</li>';
        }

        return $branch_html;
    };

    $list_html = $render_page_branch($root_id, 1);
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
            . '<div class="own-zijbalk-navigatie-row flex items-center gap-2 py-2.5 px-3 rounded-xl">'
            . '<a href="#' . esc_attr($heading['anchor']) . '" class="own-zijbalk-navigatie-link min-w-0 truncate">' . esc_html($heading['label']) . '</a>'
            . '</div></li>';
    }
}

$has_items = trim($list_html) !== '';

if (!$has_items && empty($is_preview)) {
    return;
}

// "own-zijbalk-navigatie" ALTIJD als laatste class-token, nooit ervoor: bij
// $is_sticky eindigt de class-attribute anders op "top-24" (een cijfer) —
// zie acf/hero's eigen docblock voor de volle wptexturize-val.
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim('relative rounded-2xl p-6' . ($is_sticky ? ' sticky top-24' : '') . ' own-zijbalk-navigatie'),
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
        <ul class="own-zijbalk-navigatie-list m-0 p-0 flex flex-col gap-1 list-none">
            <?= $list_html; ?>
        </ul>
    <?php else : ?>
        <p class="opacity-50 text-sm"><?php esc_html_e('Nog niets om te tonen — kies pagina\'s met subpagina\'s, of zet een HTML-anker op een kop.', 'own-framework'); ?></p>
    <?php endif; ?>
</nav>
