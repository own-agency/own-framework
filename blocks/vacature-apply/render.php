<?php
defined('ABSPATH') || exit();

/**
 * Sollicitatiekaart voor de vacature-detailpagina — het rechter (1/3) blok
 * naast de vacaturetekst, direct in templates/single-vacancy.html geplaatst
 * (niet in de vacature's eigen post-content): vacatures komen hier zowel via
 * de block-editor als via WP All Import binnen, en een import zet
 * post_content rechtstreeks in de database zonder ooit de block-editor's
 * "Nieuwe vacature toevoegen"-flow te doorlopen — een eerdere per-post
 * "template"-argument (register_post_type_args) werkte daardoor alleen voor
 * handmatig aangemaakte vacatures. In de TEMPLATE geplaatst geldt de 2/3+1/3
 * kolommen-layout (en dus dit block) voor elke vacature, ongeacht hoe hij is
 * aangemaakt. Zie hooks/restrict-to-vacancy-context.php voor waar dit block
 * wél/niet in de inserter mag verschijnen.
 *
 * Sollicitatiemethode ("form" vs "url") is een SITE-BREDE keuze (Thema opties
 * → Vacatures → Sollicitatiemethode) — expliciet zo gekozen, niet per
 * vacature: elke vacature gebruikt dezelfde methode. De sollicitatie-URL
 * zelf is wél per vacature (field_own7a1b2c3d4e602 op de "Vacature"-group),
 * want die verschilt uiteraard per functie/werkgever.
 *
 * get_field('application_url', $post_id) met een EXPLICIETE post-ID, niet
 * get_field('application_url'): dit block rendert zelf binnen een SCF-block-
 * renderpad, en ACF's eigen block-rendering laat get_field() zonder post-ID
 * dan tegen het block's EIGEN (niet-bestaande) post-ID resolven i.p.v.
 * get_the_ID() — exact dezelfde valkuil als blocks/vacatures/parts/card.php's
 * "logo"-veld al eerder had (secure-custom-fields/includes/local-meta.php's
 * ACF_Local_Meta), dus hier vanaf het begin al voorkomen.
 *
 * "Formulier"-methode: het formulier zelf is Forminator Pro (shortcode
 * [forminator_form id="…"], plugin wordt los geïnstalleerd/geconfigureerd —
 * geen eigen REST/wp_mail-mechanisme hier). shortcode_exists() voorkomt dat
 * de kale "[forminator_form id="…"]"-tekst zichtbaar wordt zolang de plugin
 * nog niet actief is; toont dan dezelfde nette lege-status als een ontbrekend
 * formulier-ID.
 *
 * Titel/tekst zijn InnerBlocks (core/heading + core/paragraph, "core/buttons"
 * ook toegestaan voor een eventuele extra/secundaire knop) i.p.v. vaste
 * strings — zelfde patroon als acf/vacature-hero's eigen $hero_inner_blocks,
 * zodat dit net zo native bewerkbaar is (eigen tekst, eigen kleur per
 * blokje via ieders NATIVE "Kleuren"-paneel) als elk ander block in dit
 * theme. De echte "Solliciteren"-knop hieronder blijft bewust GEEN
 * InnerBlocks-kind: die knop's gedrag (externe link vs. Forminator-modal)
 * is data-gedreven (site-brede instelling + per-vacature URL, en dit block
 * staat bovendien in de TEMPLATE, gedeeld door elke vacature — een native
 * knop's url-attribute is een vaste waarde per block-instance, kan dus nooit
 * per bekeken vacature verschillen), niet iets een editor los zou kunnen
 * intypen. De knop gebruikt daarom WEL letterlijk de site-brede Knop-classes
 * ("wp-element-button wp-block-button__link") in plaats van eigen kleur-/
 * hoekafrondingattributes zoals acf/vacature-hero's eigen zoekknop — die
 * conflict-met-de-native-kleur-styling-reden (CLAUDE.md Traps #7) speelt hier
 * niet: deze knop heeft bewust GEEN eigen per-instance kleur meer, dus volgt
 * hij gewoon Ontwerp → Stijlen → Knoppen, exact zoals een echt Knop-block.
 */

$post_id = get_the_ID();

$apply_method = get_field('sollicitatiemethode', 'option') ?: 'form';
$application_url = get_field('application_url', $post_id) ?: '';
$forminator_form_id = get_field('forminator_form_id', 'option') ?: '';
$has_forminator_form = $forminator_form_id !== '' && shortcode_exists('forminator_form');

// Explicit post-ID, same reason as $application_url above. Same field as
// blocks/vacatures/parts/card.php's own logo (group_own7a1b2c3d4e5f6, name
// "logo") — the archive card and this card are the two places on the site
// that show it.
$logo = get_field('logo', $post_id);

$card_bg_color = $block['cardBackgroundColor'] ?? '';

$style_parts = [];
if ($card_bg_color !== '') {
    $style_parts[] = '--own-vacature-apply-card-bg-color:' . esc_attr($card_bg_color);
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'own-vacature-apply',
    'style' => implode(';', $style_parts),
]);

$inner_blocks = '<InnerBlocks '
    . 'template=\'[["core/heading",{"level":3,"placeholder":"Solliciteren","className":"text-lg font-semibold m-0"}],["core/paragraph",{"placeholder":"Interesse in deze vacature? Solliciteer direct.","className":"mt-2 text-sm opacity-80"}]]\' '
    . 'allowedBlocks=\'["core/heading","core/paragraph","core/buttons"]\' '
    . 'templateLock=\'false\' '
    . 'className=\'space-y-2\' />';
?>
<div <?= $wrapper_attributes; ?> x-data="{ applyModalOpen: false }" @keydown.escape.window="applyModalOpen = false">
    <div class="own-vacature-apply__card own-vacatures-surface bg-(--own-vacature-apply-card-bg-color) rounded-2xl p-6 sticky top-24 [&_:is(h1,h2,h3,h4,h5,h6)]:mt-0!">
        <?php if (!empty($logo['url'])) : ?>
            <?php // Explicit width/height, not just max-h-10/w-auto/h-auto: same
            // reasoning as parts/card.php's own logo — a logo upload is very
            // often an SVG, and without these two HTML attributes some
            // browsers fall back to a default intrinsic size before the
            // stylesheet has resolved, instead of the file's own real ratio. ?>
            <img
                class="max-h-10 max-w-full w-auto h-auto mb-4 object-contain"
                src="<?= esc_url($logo['url']); ?>"
                width="<?= (int) ($logo['width'] ?? 160); ?>"
                height="<?= (int) ($logo['height'] ?? 40); ?>"
                alt="<?= esc_attr($logo['alt'] ?? ''); ?>">
            <hr class="mb-4 border-gray-100">
        <?php endif; ?>
        <?= $inner_blocks; ?>

        <?php if ($apply_method === 'url' && $application_url !== '') : ?>
            <div class="wp-block-button">
                <a
                    href="<?= esc_url($application_url); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="wp-block-button__link wp-element-button mt-5 w-full">
                    <?php esc_html_e('Solliciteer direct', 'own-framework'); ?>
                </a>
            </div>
        <?php elseif ($apply_method === 'form') : ?>
            <div class="wp-block-button">
                <button
                    type="button"
                    @click="applyModalOpen = true"
                    class="wp-block-button__link wp-element-button mt-5 w-full">
                    <?php esc_html_e('Solliciteer direct', 'own-framework'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($apply_method === 'form') : ?>
        <div x-show="applyModalOpen" x-cloak class="own-vacature-apply-modal fixed inset-0 z-50 flex items-center justify-center p-4">
            <div
                x-show="applyModalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="applyModalOpen = false"
                class="absolute inset-0 bg-black/50"
                aria-hidden="true"></div>

            <div
                x-show="applyModalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-4"
                @click.outside="applyModalOpen = false"
                class="own-vacature-apply-modal__panel relative w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-xl bg-white shadow-xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="own-vacature-apply-modal-title">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 id="own-vacature-apply-modal-title" class="text-lg font-semibold text-gray-900 m-0!">
                        <?php
                        printf(
                            /* translators: %s: vacancy title */
                            esc_html__('Solliciteren op "%s"', 'own-framework'),
                            esc_html(get_the_title($post_id))
                        );
                        ?>
                    </h3>
                    <button type="button" @click="applyModalOpen = false" class="own-vacatures-icon-btn rounded-lg p-2 text-gray-400" aria-label="<?php esc_attr_e('Sluiten', 'own-framework'); ?>">
                        <?= ownframework_get_icon('close', 'w-4 h-4'); ?>
                    </button>
                </div>
                <div class="p-5">
                    <?php if ($has_forminator_form) : ?>
                        <?= do_shortcode('[forminator_form id="' . esc_attr($forminator_form_id) . '"]'); ?>
                    <?php else : ?>
                        <p class="text-gray-500 text-center"><?php esc_html_e('Er is momenteel geen sollicitatieformulier gekoppeld.', 'own-framework'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>