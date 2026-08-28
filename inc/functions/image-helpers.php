<?php
defined('ABSPATH') || exit();

/**
 * Haalt de huidige url/alt van een "image"-block-attribute op via het
 * attachment-ID, met terugval op de opgeslagen waarde voor een afbeelding
 * zonder (geldig) ID. Via het ID i.p.v. de opgeslagen url/alt zelf betekent
 * dat een later aangepaste titel/alt-tekst in de mediatheek ook meteen klopt
 * in elk block dat deze afbeelding al eerder koos.
 *
 * Gedeeld door acf/cta en acf/content-blok — bij een 2e consument geëxtraheerd
 * uit acf/cta's eigen render.php i.p.v. de closure te blijven kopiëren, zelfde
 * regel als STRUCTURE.md's "een 2e consument verschijnt" voor template-parts.
 */
function ownframework_resolve_block_image(array $image): array
{
    $id = (int) ($image['id'] ?? 0);
    return [
        'url' => $id ? (wp_get_attachment_image_url($id, 'full') ?: '') : ($image['url'] ?? ''),
        'alt' => $id ? trim((string) get_post_meta($id, '_wp_attachment_image_alt', true)) : ($image['alt'] ?? ''),
    ];
}

/**
 * Zet een FocalPointPicker-waarde (x/y als fractie, 0–1) om naar een
 * "object-position"-waarde. object-cover snijdt de afbeelding altijd bij,
 * dus zonder dit staat dat brandpunt altijd op het midden (browserdefault)
 * i.p.v. waar de editor het heeft gekozen. Gedeeld door elk block met een
 * eigen "<iets>FocalPoint"/"<iets>Position"-attribute naast een gewoon
 * "image"/"images"-attribute (acf/hero, acf/cta, acf/content-blok, acf/
 * afbeeldingen-slider, acf/verhalen) — bij de tweede consumer al uit acf/
 * hero's eigen render.php geëxtraheerd i.p.v. de closure te blijven kopiëren,
 * zelfde regel als ownframework_resolve_block_image()'s eigen docblock.
 */
function ownframework_get_image_object_position(array $position): string
{
    return sprintf(
        '%s%% %s%%',
        round(((float) ($position['x'] ?? 0.5)) * 100, 2),
        round(((float) ($position['y'] ?? 0.5)) * 100, 2)
    );
}
