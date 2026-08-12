<?php
defined("ABSPATH") || exit();

/**
 * "Ruimteschaal" (Thema opties → Stijlen → Spacing, acf-json/group_own_
 * style_settings.json) — een algehele stijlkeuze voor de hele site, niet
 * iets per block. In tegenstelling tot radius-scale.php's eigen CSS-
 * variabele-override heeft theme.json wél een echte preset-schaal voor
 * spacing (settings.spacing.spacingSizes, dezelfde XS–XL die de native
 * "Ruimte"-keuzelijst bij marge/padding/blockGap overal in de editor
 * gebruikt) — dus dít kan via de "wp_theme_json_data_theme"-filter écht
 * theme.json's eigen data aanpassen i.p.v. er alleen met CSS overheen te
 * schilderen. Resultaat: de editor's EIGEN keuzelijst toont per site de
 * daadwerkelijk gekozen schaal, niet alleen het front-end resultaat.
 *
 * Bewust NIET de theme.json-BESTAND zelf herschrijven: zodra een editor ooit
 * iets via het "Ontwerp"-paneel aanpast, slaat WordPress dat op als een apart,
 * in de database opgeslagen "user"-niveau dat theme.json's eigen bestand
 * ALTIJD overstemt — een bestandsaanpassing hier zou dan stil genegeerd
 * worden, terwijl deze filter op precies dezelfde laag als theme.json zelf
 * meedraait en zich keurig laat overstemmen door zo'n echte, bewuste
 * pagina-specifieke aanpassing (zoals het altijd al zou moeten).
 *
 * $theme_json->get_data() geeft de HUIDIGE, al samengevoegde data terug —
 * alleen spacingSizes vervangen (i.p.v. een geheel nieuwe array teruggeven
 * aan update_with()) laat de rest van theme.json (kleuren, fonts, shadows)
 * onaangeroerd.
 */
add_filter("wp_theme_json_data_theme", function ($theme_json) {
    $spacing_field = get_field("spacing", "option") ?: [];
    $scale = $spacing_field["scale"] ?? "standard";

    // Eén tandje opgeschoven t.o.v. de vorige versie: "compact" kreeg wat
    // eerst "standaard" was, "standaard" (de default) kreeg wat eerst "ruim"
    // was, en "ruim" is een geheel nieuwe, nog grotere schaal — dezelfde
    // namen/slugs, andere waarden erachter.
    $sizes = match ($scale) {
        "compact" => [
            "xs" => ["8–12px", "clamp(0.5rem, 0.41rem + 0.38vw, 0.75rem)"],
            "s" => ["16–20px", "clamp(1rem, 0.91rem + 0.4vw, 1.25rem)"],
            "m" => ["24–32px", "clamp(1.5rem, 1.32rem + 0.75vw, 2rem)"],
            "l" => ["32–48px", "clamp(2rem, 1.65rem + 1.5vw, 3rem)"],
            "xl" => ["44–64px", "clamp(2.75rem, 2.3rem + 1.9vw, 4rem)"],
        ],
        "spacious" => [
            "xs" => ["18–28px", "clamp(1.125rem, 0.93rem + 0.94vw, 1.75rem)"],
            "s" => ["36–48px", "clamp(2.25rem, 1.87rem + 1.13vw, 3rem)"],
            "m" => ["54–78px", "clamp(3.375rem, 2.8rem + 2.25vw, 4.875rem)"],
            "l" => ["78–114px", "clamp(4.875rem, 4.05rem + 3.38vw, 7.125rem)"],
            "xl" => ["108–156px", "clamp(6.75rem, 5.6rem + 4.5vw, 9.75rem)"],
        ],
        // "standard": wat vóór deze verschuiving "ruim" was — de default
        // hierboven (ACF-veld EN deze match) zodat een site zonder expliciete
        // keuze op de nieuwe, grotere "standaard" schaal landt.
        default => [
            "xs" => ["12–18px", "clamp(0.75rem, 0.6rem + 0.75vw, 1.125rem)"],
            "s" => ["24–32px", "clamp(1.5rem, 1.3rem + 1vw, 2rem)"],
            "m" => ["36–52px", "clamp(2.25rem, 1.9rem + 1.75vw, 3.25rem)"],
            "l" => ["52–76px", "clamp(3.25rem, 2.7rem + 2.75vw, 4.75rem)"],
            "xl" => ["72–104px", "clamp(4.5rem, 3.75rem + 3.75vw, 6.5rem)"],
        ],
    };

    $labels = ["xs" => "XS", "s" => "S", "m" => "M", "l" => "L", "xl" => "XL"];
    $spacing_sizes = [];
    foreach ($sizes as $slug => [$range, $size]) {
        $spacing_sizes[] = [
            "name" => "{$labels[$slug]} ({$range})",
            "slug" => $slug,
            "size" => $size,
        ];
    }

    $data = $theme_json->get_data();
    $data["settings"]["spacing"]["spacingSizes"] = $spacing_sizes;

    return $theme_json->update_with($data);
});
