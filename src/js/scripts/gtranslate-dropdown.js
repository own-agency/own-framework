// GTranslate's own "Dropdown" widget (dropdown.js, the gtranslate plugin)
// always lists full language names in its <option> text — this theme only
// wants the language CODE ("NL", "EN", …) to match the header's other
// compact controls. There's no "show codes" setting for this particular
// widget_look (only the wholly different "flags_dropdown"/"lang_codes"
// looks build that, an entirely different, non-<select> UI this theme
// doesn't use) — so this rewrites each <option>'s text itself, right after
// the plugin creates it. Each <option>'s own value is
// "<default_language>|<target_language>" (dropdown.js's own el_o.value),
// so the code is just the part after the "|" — no separate lookup needed.
//
// MutationObserver, not a fixed delay: there's no reliable signal for
// exactly when the plugin's own script finishes (it can load from its own
// CDN or a local file, wp_enqueue_script('gt_widget_script_...'), and this
// theme's own bundle has no fixed load-order relationship with it either
// way).
function toCodeLabels(select) {
  select.querySelectorAll("option").forEach((option) => {
    const code = option.value.split("|")[1];
    if (code) {
      option.textContent = code.toUpperCase();
    }
  });
}

document.querySelectorAll(".gtranslate_wrapper").forEach((wrapper) => {
  const existing = wrapper.querySelector("select.gt_selector");
  if (existing) {
    toCodeLabels(existing);
    return;
  }

  const observer = new MutationObserver(() => {
    const select = wrapper.querySelector("select.gt_selector");
    if (select) {
      toCodeLabels(select);
      observer.disconnect();
    }
  });
  observer.observe(wrapper, { childList: true, subtree: true });
});
