<?php
defined('ABSPATH') || exit();

/**
 * Logo slider — een oneindig doorlopende band ("marquee") met logo's van
 * klanten/partners. Vervangt een eerdere Swiper-gebaseerde versie van dit
 * block volledig: voor een puur decoratieve, altijd-doorlopende band is een
 * CSS `@keyframes`-animatie (src/css/components/logo-slider.css) genoeg —
 * geen JS-library, geen enkel script, ook niet in de editor. Dat is een
 * bewuste vereenvoudiging, niet een tussenstap: er is geen "Swiper-instance"
 * meer om te initialiseren, dus front-end en editor tonen exact dezelfde
 * markup zonder dat daar iets voor moet draaien.
 *
 * NAADLOOS LOOPEN: de logo's-array wordt hieronder precies TWEE keer
 * gerenderd, direct na elkaar in dezelfde track ($is_primary_set-loop). De
 * animatie schuift de hele track exact 50% van zijn eigen breedte op
 * (logo-slider.css' "translateX(-50%)") — omdat de tweede helft van de track
 * letterlijk een kopie van de eerste is, sluit die naadloos aan zodra de
 * animatie weer bij 0% begint. Dit werkt bij ELK aantal logo's (zelfs 1),
 * anders dan Swipers eigen "loop"-modus die genoeg ORIGINELE slides nodig
 * heeft om de viewport een paar keer te vullen voor het naadloos wordt. De
 * tweede set krijgt "aria-hidden" en een leeg alt-attribuut: puur een visuele
 * kopie, een schermlezer moet 'm niet als aparte logo's voorlezen.
 *
 * TWEE ELEMENTEN, NIET ÉÉN: de sectie (<section>, altijd op de normale,
 * native align-breedte — ook mét "fullWidth" aan, zodat de titel daar NOOIT
 * in meebeweegt) en daarbinnen ".own-logo-slider-viewport" (alleen om de
 * track, niet om de titel), dat bij "fullWidth" zelf de "left-1/2 +
 * -translate-x-1/2 + w-screen"-truc krijgt. Dat is bewust een ANDERE plek dan
 * een eerdere versie van dit bestand, die de truc rechtstreeks op de sectie
 * zette — en daardoor NIET tot de echte schermranden bleedde. Reden: de
 * sectie zelf krijgt zijn breedte/centrering al van WordPress' "constrained"
 * layout (`.is-layout-constrained > * { max-width:…; margin-inline:auto }`,
 * core's eigen CSS voor "" / "wide"), dus de sectie is NIET gewoon "een kind
 * met margin-inline:auto binnen een volle-breedte-ouder" — hij is zelf al
 * gecentreerd tegen zijn EIGEN ouder (core/post-content). Percentages voor
 * "left" op een `position:relative`-element resolven tegen de breedte van
 * ZIJN OUDER (niet tegen zichzelf), dus "left-1/2" op de sectie zelf
 * resolveerde tegen die (volle-breedte) ouder, terwijl de sectie's eigen
 * (al gecentreerde) statische positie daar nog eens BOVENOP kwam — dat
 * dubbelt de centrering i.p.v. 'm te compenseren, en bleedt daardoor maar
 * half zo ver als de schermrand.
 * ".own-logo-slider-viewport" zit hier één laag dieper: de SECTIE (na zijn
 * eigen centrering) is nu de ouder waar "left-1/2" tegen resolveert, en de
 * viewport-div zelf heeft geen eigen margin-auto-centrering — zijn statische
 * positie staat gewoon vlak tegen de sectie's linkerrand. Die twee dingen
 * samen zijn precies wat "left-1/2 + -translate-x-1/2 + w-screen" nodig
 * heeft om kloppend te resolven naar de echte viewport-rand, ongeacht hoe
 * breed de sectie zelf is (""/"wide") — zelfde wiskunde als
 * `ownframework_full_bleed_class()` (inc/functions/full-bleed.php) al
 * gebruikt, alleen daar via een ABSOLUTE kind-laag i.p.v. hier een
 * RELATIVE kind-laag (nodig omdat de track hier, anders dan bij hero/cta's
 * achtergrond, de eigenlijke INHOUD is en dus in de normale flow moet
 * blijven staan — een absolute laag neemt geen ruimte meer in).
 * "html { overflow-x: clip }" (src/css/app.css, daar toegevoegd voor precies
 * dit soort 100vw-content) voorkomt een horizontale scrollbar.
 * Zonder "fullWidth" blijft ".own-logo-slider-viewport" gewoon binnen de
 * sectie's eigen breedte — geen ".container" (CLAUDE.md Traps #12): dat is
 * voor hero/cta's legacy align="full"-inhoud, niet voor nieuwe blocks.
 *
 * "own-logo-slider-fade" (alleen ZONDER "fullWidth" — zie editor.js' eigen
 * hulptekst) is een CSS mask-image (logo-slider.css) die de logo's aan de
 * linker/rechterrand van de viewport laat wegvloeien i.p.v. daar hard af te
 * knippen. Bij "fullWidth" ontbreekt die rand simpelweg (de band loopt tot
 * de echte schermrand), dus is er niets om weg te laten vervloeien.
 *
 * Achtergrondkleur gebruikt WEL hetzelfde full-bleed-laagje als acf/feitjes-
 * slider/acf/content-blokken-slider (ownframework_full_bleed_class()/
 * _background(), inc/functions/full-bleed.php) — een eerdere versie van dit
 * bestand liet de kleur bewust puur uit het native "Kleuren"-paneel komen
 * (dus stoppend bij de sectie's eigen align-breedte, 1100/1280px), met als
 * argument dat de sectie toch al op zijn align-breedte blijft (zie
 * hierboven). Dat argument ging alleen over de MARQUEE-band zelf (met
 * "fullWidth" AAN bewust edge-to-edge, anders niet) — niet over de
 * ACHTERGRONDKLEUR, die net als bij elk ander block met een gekleurde sectie
 * altijd tot de echte schermranden hoort te lopen, ongeacht "fullWidth".
 * Zonder het full-bleed-laagje stopte een gekozen achtergrondkleur dus bij
 * de align-breedte terwijl elk ander block op de site wél doorliep tot de
 * schermrand — inconsistent, geen bewuste ontwerpkeuze.
 * get_block_wrapper_attributes() zet de kleur nog steeds ook automatisch op
 * de sectie zelf (native "Kleuren"-paneel) — onschadelijk, dat valt
 * volledig onder het bredere laagje, zelfde reden als bij feitjes-slider.
 * De sectie moet daarom zelf "position: relative" zijn (root_class
 * hieronder) — anders centreert het laagje tegen een verder weg gelegen
 * voorouder (zie de helper's eigen docblock) — en de titel/viewport staan nu
 * in hun eigen "relative z-10"-laag zodat ze boven de achtergrond blijven.
 * Die extra wrapper-laag verandert NIETS aan ".own-logo-slider-viewport"'s
 * eigen "left-1/2"-wiskunde hierboven: een percentage voor "position:
 * relative" resolvet tegen de content-box-breedte van de eigen ouder, en
 * deze wrapper-div heeft zelf geen padding/margin/max-width — zijn
 * content-box is dus exact even breed (en even gecentreerd) als de sectie's
 * eigen, precies wat die berekening al nodig had.
 *
 * "logoSize" (small/medium/large → $height_class) zet bewust alleen de
 * HOOGTE vast, niet de breedte (anders dan de referentie-implementatie waar
 * dit vandaan komt) — object-contain, geen object-cover: zo ogen een
 * vierkant en een liggend logo toch even "zwaar" naast elkaar zonder dat er
 * ooit gecropt wordt.
 *
 * "speed" (slow/medium/fast → $duration_map) is nu een vaste duur in
 * seconden per volledige ronde, onafhankelijk van het aantal logo's — geen
 * herberekening op basis van track-breedte nodig.
 *
 * Val nooit met een class="...-<cijfer>" op het einde van een class-attribute
 * i.p.v. op een letter of "!": wptexturize() zet een cijfer direct gevolgd
 * door een aanhalingsteken om in een dubbele-prime-entity, wat het sluitende
 * aanhalingsteken kapotmaakt. Class-lijsten hieronder zijn daarom zo
 * geordend dat ze altijd op een letter (of "!") eindigen, nooit op een
 * cijfer — "m-0!" op elk logo staat daarom altijd achteraan, net als bij
 * acf/hero/acf/cta/acf/content-blok/acf/afbeeldingen-slider's eigen
 * <img>-tags (zonder dit pakt Tailwind Typography's ".prose img"-regel,
 * template/page.html's "prose"-className, elk logo een ongewenste
 * 2em-marge af — zie CLAUDE.md, Traps #19).
 */

$title = trim($block['title'] ?? '');
$logos = $block['logos'] ?? [];
$direction = ($block['direction'] ?? 'left') === 'right' ? 'right' : 'left';
$grayscale = !empty($block['grayscale']);
$pause_on_hover = !empty($block['pauseOnHover']);
$full_width = !empty($block['fullWidth']);

// Voor het full-bleed-achtergrondlaagje hieronder — zelfde extractie als
// acf/feitjes-slider/acf/content-blokken-slider.
if (!empty($block['style']['color']['background'])) {
    $bg_value = $block['style']['color']['background'];
} elseif (!empty($block['backgroundColor'])) {
    $bg_value = 'var(--wp--preset--color--' . $block['backgroundColor'] . ')';
} else {
    $bg_value = '';
}

// Een logo zonder gekozen afbeelding (bv. net verwijderd uit de mediatheek)
// rendert geen kapotte <img> — eruit filteren i.p.v. meetellen als slide.
$logos = array_values(array_filter($logos, fn($logo) => !empty($logo['url'])));

// Alleen de 3 keuzes uit editor.js; alles anders valt terug op "medium" i.p.v.
// een onbekende sleutel te gebruiken.
$duration_map = ['slow' => 50, 'medium' => 35, 'fast' => 20];
$speed_key = $block['speed'] ?? 'medium';
if (!isset($duration_map[$speed_key])) {
    $speed_key = 'medium';
}
$duration = $duration_map[$speed_key];

// Twee stappen per keuze (mobiel/vanaf "sm"), zelfde responsive-conventie als
// acf/hero/acf/google-maps's eigen hoogtekeuzes.
$height_class = match ($block['logoSize'] ?? 'medium') {
    'small' => 'h-8 sm:h-10',
    'large' => 'h-12 sm:h-16',
    default => 'h-9 sm:h-11',
};

// get_block_wrapper_attributes() vertaalt de align-attribute bij een SCF-block
// met "mode": "preview" niet betrouwbaar naar een "alignwide"-klasse op de
// front-end — die dus zelf toevoegen, zelfde aanpak als acf/afbeeldingen-
// slider en acf/hero. Altijd toepassen, ook mét "fullWidth": de sectie zelf
// (en dus de titel erin) blijft altijd op de align-breedte, zie de docblock
// hierboven voor waarom alleen ".own-logo-slider-viewport" (niet de sectie)
// de schermbreedte-truc krijgt.
$align = $block['align'] ?? '';
$align_class = $align !== '' ? 'align' . $align : '';

// "relative" is nodig zodat ownframework_full_bleed_class()'s eigen absolute
// laag hieronder tegen DEZE sectie centreert i.p.v. tegen een verder weg
// gelegen voorouder (zie die helper's eigen docblock) — verandert niets aan
// ".own-logo-slider-viewport"'s eigen "left-1/2"-truc verderop, zie de
// docblock bovenaan dit bestand voor waarom.
$root_class = implode(' ', array_filter([
    'own-logo-slider',
    'relative',
    $align_class,
]));

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => $root_class,
]);

// Zie de docblock hierboven voor de wiskunde: dit werkt alleen kloppend
// omdat ".own-logo-slider-viewport" zelf GEEN eigen margin-auto-centrering
// heeft (anders dan de sectie erboven) — zijn ouder (de sectie, na Zijn
// eigen centrering) is de enige referentie voor "left-1/2". Eindigt zelf op
// een cijfer ("-translate-x-1/2"), dus nooit als laatste in de class-lijst;
// array_filter()+implode() hieronder garandeert dat "overflow-hidden" altijd
// de laatste, altijd aanwezige class is.
$viewport_class = implode(' ', array_filter([
    'own-logo-slider-viewport',
    'py-6',
    'sm:py-8',
    $full_width ? 'relative left-1/2 -translate-x-1/2 w-screen' : '',
    !$full_width ? 'own-logo-slider-fade' : '',
    'overflow-hidden',
]));

// "own-logo-slider-reverse" i.p.v. een tweede @keyframes: logo-slider.css'
// enige animatie omgekeerd afspelen geeft dezelfde "naar rechts"-beweging.
// "items-center" altijd als laatste, gegarandeerde class (zie hierboven):
// "gap-20"/"md:gap-28" eindigen zelf op een cijfer.
$track_class = implode(' ', array_filter([
    'own-logo-slider-track',
    'gap-20',
    'md:gap-28',
    $direction === 'right' ? 'own-logo-slider-reverse' : '',
    'flex',
    'w-max',
    'items-center',
]));
?>
<section <?= $wrapper_attributes; ?>>
    <?= ownframework_full_bleed_background($bg_value); ?>
    <div class="relative z-10">
        <?php if ($title !== '') : ?>
            <h2 class="mb-8 sm:mb-10 mt-0!"><?= esc_html($title); ?></h2>
        <?php elseif (!empty($is_preview)) : ?>
            <h2 class="opacity-50 mb-8 sm:mb-10 mt-0!"><?php esc_html_e('Titel…', 'own-framework'); ?></h2>
        <?php endif; ?>

        <?php if (empty($logos)) : ?>
            <p class="mb-0 text-sm text-center"><?= esc_html__("Er zijn nog geen logo's toegevoegd.", 'own-framework'); ?></p>
        <?php else : ?>
            <div class="<?= esc_attr($viewport_class); ?><?= $pause_on_hover ? ' own-logo-slider-pause-hover' : ''; ?>">
                <div class="<?= esc_attr($track_class); ?>" style="animation-duration:<?= (int) $duration; ?>s">
                    <?php foreach ([true, false] as $is_primary_set) : ?>
                        <?php foreach ($logos as $logo) : ?>
                            <div class="flex shrink-0 items-center justify-center">
                                <img
                                    src="<?= esc_url($logo['url']); ?>"
                                    alt="<?= $is_primary_set ? esc_attr($logo['alt'] ?? '') : ''; ?>"
                                    <?= $is_primary_set ? '' : 'aria-hidden="true"'; ?>
                                    loading="lazy"
                                    decoding="async"
                                    class="own-logo-slider-logo object-contain w-auto max-w-full <?= esc_attr($height_class); ?> <?= $grayscale ? 'grayscale ' : ''; ?>m-0!">
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
