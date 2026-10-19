<?php
defined('ABSPATH') || exit();

/**
 * Mobile drilldown navigation for blocks/header/parts/mobile-menu.php (its
 * only consumer). Every item with children gets its own panel that slides in
 * from the right, at any depth — the panel STACK is Alpine state declared by
 * mobile-menu.php itself (this class only emits markup/classes that read
 * it), CSS transitions handle the animation. Adapted from a sibling OWN
 * project's own mobile menu (same drilldown UX) — icon/colour choices below
 * are this theme's own (ownframework_get_icon(), --wp--preset--color--
 * primary), not a verbatim port.
 *
 * A class, not the usual ownframework_ prefixed function, because the
 * original (and this) needs private recursive helpers (buildTree,
 * renderSubPanel) without leaking them into the global function namespace.
 *
 * Usage: <?= Ownframework_Mobile_Drilldown_Walker::render('primary-menu'); ?>
 */
class Ownframework_Mobile_Drilldown_Walker
{
    public static function render(string $themeLocation): string
    {
        $locations = get_nav_menu_locations();
        if (empty($locations[$themeLocation])) {
            return '';
        }

        $menu = wp_get_nav_menu_object($locations[$themeLocation]);
        if (!$menu) {
            return '';
        }

        $items = wp_get_nav_menu_items($menu->term_id, ['order' => 'ASC']);
        if (!$items) {
            return '';
        }

        _wp_menu_item_classes_by_context($items);

        $tree = self::buildTree($items);

        $out  = '<div';
        $out .= ' x-data="{ panels: [\'root\'] }"';
        // mobileMenuOpen is the ANCESTOR <header>'s own Alpine state
        // (render.php), not this div's — resets the drilldown stack once the
        // whole off-canvas panel closes, so reopening it always starts back
        // at the root list instead of wherever the visitor last drilled to.
        // esc_attr(), not pasted in raw: this expression's own "=>" arrows
        // contain a literal ">", which wptexturize() treats as a tag close
        // when scanning raw HTML output — everything after it (including the
        // real closing quote of THIS attribute) then reads as "outside a
        // tag" to it, and any following straight quote gets smart-quoted
        // into a curly one, corrupting this and every attribute after it.
        // esc_attr() turns ">" into "&gt;", which wptexturize() doesn't
        // recognize as closing a tag, and which the browser decodes right
        // back into a literal ">" when it parses the attribute value — see
        // render.php's own $scroll_expr for the exact same fix, same reason.
        $out .= ' x-init="' . esc_attr('$watch(\'mobileMenuOpen\', v => { if (!v) $nextTick(() => panels = [\'root\']) })') . '"';
        $out .= ' class="relative flex-1 overflow-hidden"';
        $out .= '>';

        // ── Root panel ────────────────────────────────────────────────────
        $out .= '<div';
        $out .= ' class="absolute inset-0 flex flex-col transition-transform duration-300 ease-in-out overflow-y-auto px-4 sm:px-6 py-2"';
        // esc_attr(): same reason as x-init above — "panels.length > 1" has
        // its own literal ">".
        $out .= ' :class="' . esc_attr('panels.length > 1 ? \'-translate-x-full\' : \'translate-x-0\'') . '"';
        $out .= '>';
        $out .= '<ul class="flex flex-col divide-y divide-black/5 shrink-0">';
        foreach ($tree as $item) {
            $out .= self::renderItem($item);
        }
        $out .= '</ul>';
        $out .= '</div>';

        // ── Sub panels, recursively, for every item with children ─────────
        foreach ($tree as $item) {
            if (!empty($item->children)) {
                $out .= self::renderSubPanel($item);
            }
        }

        $out .= '</div>';

        return $out;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private static function buildTree(array $items, int $parentId = 0): array
    {
        $branch = [];
        foreach ($items as $item) {
            if ((int) $item->menu_item_parent === $parentId) {
                $item->children = self::buildTree($items, $item->ID);
                $branch[] = $item;
            }
        }
        return $branch;
    }

    private static function isActive(object $item): bool
    {
        return (bool) array_intersect(
            ['current-menu-item', 'current-menu-ancestor', 'current-page-ancestor'],
            (array) $item->classes
        );
    }

    // Active highlight for both row types (drilldown trigger and leaf link).
    private const ACTIVE_LABEL_CLASS = 'underline decoration-2 underline-offset-4';

    // Leaf links (and a parent's own "Ga naar …" link) are only "active" when
    // they ARE the current page — isActive()'s ancestor match would also mark
    // "Ga naar Voor ouders" while viewing one of its children.
    private static function isCurrent(object $item): bool
    {
        return in_array('current-menu-item', (array) $item->classes, true);
    }

    private static function linkAttrs(object $item): string
    {
        $attrs  = ' href="' . esc_url($item->url ?: '#') . '"';
        $attrs .= !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
        $attrs .= $item->target === '_blank' ? ' rel="noopener noreferrer"' : '';
        $attrs .= self::isCurrent($item) ? ' aria-current="page"' : '';
        return $attrs;
    }

    // No dedicated "arrow-right" asset in media/icons/ — chevron-right.svg
    // already IS this theme's own "opens further" indicator (desktop
    // dropdown/flyout, nav-chevrons.php), so drilldown reuses the exact same
    // icon rather than introducing a second one that means the same thing.
    private static function chevronRight(string $class = 'size-5 shrink-0'): string
    {
        return ownframework_get_icon('chevron-right', $class);
    }

    // No colour class here on purpose — this "Terug" chevron should just
    // read as the surrounding text's own colour (currentColor, via the SVG's
    // stroke="currentColor"), not a fixed brand accent unrelated to whatever
    // colour that text happens to be.
    private static function chevronLeft(string $class = 'size-5 shrink-0'): string
    {
        return '<span class="rotate-180 inline-flex">' . ownframework_get_icon('chevron-right', $class) . '</span>';
    }

    // Items with children are drilldown triggers: the whole row pushes a new
    // panel, so there's no competing click target within the same row.
    private static function renderItem(object $item): string
    {
        if (empty($item->children)) {
            return self::renderLeafLink($item);
        }

        $label   = esc_html($item->title);
        $active  = self::isActive($item);
        $panelId = 'panel-' . $item->ID;

        // Active = underlined label, no colour change: the row keeps the
        // panel's own text colour, so the highlight works on any header colour.
        $btnCls = 'group w-full flex items-center justify-between gap-3 py-3.5 cursor-pointer transition-colors duration-150';
        $labelCls = 'font-medium! min-w-0 flex-1 text-left' . ($active ? ' ' . self::ACTIVE_LABEL_CLASS : '');

        $out  = '<li>';
        $out .= '<button';
        $out .= ' type="button"';
        $out .= ' @click="panels.push(\'' . esc_attr($panelId) . '\')"';
        $out .= ' class="' . esc_attr($btnCls) . '"';
        $out .= ' aria-label="' . esc_attr(sprintf(__('Open submenu %s', 'own-framework'), $item->title)) . '"';
        $out .= '>';
        $out .= '<span class="' . esc_attr($labelCls) . '">' . $label . '</span>';
        $out .= self::chevronRight('size-5 shrink-0 transition-transform duration-150 group-hover:translate-x-0.5');
        $out .= '</button>';
        $out .= '</li>';

        return $out;
    }

    // Also surfaces a parent's own link as the first item of its submenu,
    // since the parent row itself becomes an unclickable drilldown button.
    //
    // style="color:inherit" inline, not a class: WP's per-block "Links"
    // colour support renders a
    // `.wp-elements-<hash> a:where(:not(.wp-element-button)){color:...}`
    // rule that hits every <a> in the header, so leaf links would otherwise
    // take the site's Links colour while drilldown triggers (<button>s) keep
    // the panel's text colour. An inline style outranks that rule without
    // !important — same fix as render.php's mega-menu column colours.
    private static function renderLeafLink(object $item, string $labelFormat = '%s'): string
    {
        $label  = sprintf($labelFormat, esc_html($item->title));
        $active = self::isCurrent($item);

        $cls = 'w-full flex items-center py-3.5 font-medium! transition-colors duration-150';
        // Underline on a <span>, not the <a>: header.css strips underlines
        // from every header link.
        if ($active) {
            $label = '<span class="' . self::ACTIVE_LABEL_CLASS . '">' . $label . '</span>';
        }

        return '<li><a' . self::linkAttrs($item) . ' style="color:inherit;" class="' . esc_attr($cls) . '">' . $label . '</a></li>';
    }

    private static function renderSubPanel(object $parent): string
    {
        $panelId = 'panel-' . $parent->ID;

        $out  = '<div';
        // Reads --own-header-bg the same way mobile-menu.php's own top-level
        // markup does — see that file for why (custom property, not
        // background-color:inherit).
        $out .= ' class="absolute inset-0 bg-(--own-header-bg) flex flex-col transition-transform duration-300 ease-in-out"';
        $out .= ' :class="panels.indexOf(\'' . esc_attr($panelId) . '\') === -1 ? \'translate-x-full\' : (panels[panels.length-1] === \'' . esc_attr($panelId) . '\' ? \'translate-x-0\' : \'-translate-x-full\')"';
        $out .= '>';

        $out .= '<div class="shrink-0 border-b border-black/5 px-4 sm:px-6">';
        $out .= '<button @click="panels.pop()" type="button" class="w-full flex items-center gap-2 py-3.5 text-sm font-medium! cursor-pointer">';
        $out .= self::chevronLeft();
        $out .= '<span>' . esc_html__('Terug', 'own-framework') . '</span>';
        $out .= '</button>';
        $out .= '</div>';

        $out .= '<div class="flex-1 overflow-y-auto px-4 sm:px-6">';
        $out .= '<ul class="flex flex-col divide-y divide-black/5">';

        $parentUrl = $parent->url ?: '#';
        if ($parentUrl && $parentUrl !== '#') {
            $out .= self::renderLeafLink($parent, esc_html__('Ga naar %s', 'own-framework'));
        }

        foreach ($parent->children as $item) {
            $out .= self::renderItem($item);
        }
        $out .= '</ul>';
        $out .= '</div>'; // end scrollable area

        $out .= '</div>'; // end panel

        foreach ($parent->children as $item) {
            if (!empty($item->children)) {
                $out .= self::renderSubPanel($item);
            }
        }

        return $out;
    }
}
