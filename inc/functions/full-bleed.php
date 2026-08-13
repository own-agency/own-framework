<?php
defined('ABSPATH') || exit();

/**
 * De "full-bleed"-truc: een laag die tot de randen van het scherm loopt terwijl
 * het block zelf gewoon zijn (align-)breedte houdt.
 *
 * Nodig omdat geen enkel custom block in deze theme nog `align: "full"`
 * ondersteunt — alleen `""` (inhoudsbreedte) en `"wide"` (zie CLAUDE.md,
 * "Alignment, width and full-bleed backgrounds"). Een achtergrondkleur op zo'n
 * block stopt daardoor bij de inhoudsbreedte, terwijl een gekleurde sectie
 * juist over de volle schermbreedte hoort te lopen. Dit lost dat op ZONDER
 * `align: "full"` terug te brengen: de inhoud blijft netjes op de gekozen
 * breedte staan, alleen de achtergrond bleedt.
 *
 * Hoe het werkt: `left-1/2` + `-translate-x-1/2` centreert een `w-screen`
 * (100vw) laag op het midden van het block zelf. Dat midden valt samen met het
 * midden van de viewport zolang het block gecentreerd in de pagina staat — bij
 * zowel `""` als `"wide"` het geval, want beide krijgen van WordPress
 * `margin-inline: auto`. `inset-y-0` laat de laag exact even hoog worden als
 * het block, inclusief de padding die een editor er via het native
 * "Afmetingen"-paneel aan geeft.
 *
 * Werkt zonder aan `src/css/app.css`'s `html { overflow-x: clip }` te komen:
 * die regel staat er al voor precies dit soort 100vw-content en voorkomt dat
 * de laag een horizontale scrollbar veroorzaakt.
 *
 * Het block dat dit gebruikt moet zelf `position: relative` zijn (anders
 * centreert de laag tegen een verder weg gelegen voorouder), en zijn eigen
 * inhoud in een `relative z-10`-laag zetten zodat die bovenop blijft.
 */
function ownframework_full_bleed_class(): string
{
    return 'absolute inset-y-0 left-1/2 w-screen -translate-x-1/2 overflow-hidden';
}

/**
 * Kant-en-klaar achtergrondlaagje voor een block dat alleen een effen kleur
 * edge-to-edge nodig heeft — geen media, geen overlay.
 *
 * Geeft een lege string terug zonder gekozen kleur, zodat de aanroep gewoon
 * onvoorwaardelijk in de markup kan staan. blocks/hero en blocks/cta gebruiken
 * `ownframework_full_bleed_class()` rechtstreeks in plaats van deze functie:
 * daar zit naast de kleur ook een afbeelding/video plus een overlay ín dezelfde
 * laag.
 *
 * @param string $background Al resolvede CSS-kleurwaarde (hex of
 *                           `var(--wp--preset--color--<slug>)`).
 */
function ownframework_full_bleed_background(string $background = ''): string
{
    if (trim($background) === '') {
        return '';
    }

    // Trailing ";" met opzet: een door de editor gekozen hexkleur eindigt zelf
    // op een cijfer (bv. "#062b20"), en een style-attribute die op een cijfer
    // eindigt sneuvelt op wptexturize() (zie de render.php-docblocks).
    return sprintf(
        '<div class="%s" style="background-color:%s;"></div>',
        esc_attr(ownframework_full_bleed_class()),
        esc_attr($background)
    );
}
