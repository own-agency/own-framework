// Dropdown/flyout/mega-menu submenus (header.css) only ever open on
// :hover/:focus-within — CSS alone can't tell "this device has no real
// hover", and a tap on a menu-item-has-children link fires its OWN default
// navigation immediately, before a touch visitor ever gets to see its
// children. matchMedia("(hover: none)") is the standard signal for "primary
// input can't hover" (touchscreens with no mouse/trackpad also present), so
// this only ever runs there — mouse/trackpad visitors keep the exact
// existing hover behaviour, completely untouched.
//
// Pattern: a first tap on a trigger link adds .is-open (header.css reveals
// the same submenu/megamenu, rotates/nudges the same chevrons, as it
// already does for :hover/:focus-within) and prevents THAT tap's own
// navigation; a second tap on the SAME, already-open trigger is left alone
// and navigates normally. This is the conventional "double tap" fix for
// hover-only nav menus on touch devices.
//
// Delegated to one listener on .own-header-menu rather than one per item —
// works at every depth wp_nav_menu() can render without needing to know how
// deep the tree goes. own-header-menu__item and own-header-submenu__item
// are the only two trigger classes handled — deliberately not the mega
// menu's own own-header-megamenu__column: a column's own children are
// already always visible once its panel is open (own-header-megamenu__
// column-links has no show/hide logic of its own at all, header.css), so a
// column title has nothing to reveal and should just navigate on the first
// tap like a plain link.
if (window.matchMedia("(hover: none)").matches) {
  const nav = document.querySelector(".own-header-menu");

  if (nav) {
    nav.addEventListener("click", (event) => {
      const trigger = event.target.closest(
        ".own-header-menu__item.menu-item-has-children > a, .own-header-submenu__item.menu-item-has-children > a",
      );
      if (!trigger) {
        return;
      }

      const item = trigger.parentElement;
      if (item.classList.contains("is-open")) {
        return;
      }

      event.preventDefault();

      // Only one open per level at a time — closing whichever sibling (if
      // any) was previously open at the SAME depth, not the whole tree.
      item.parentElement
        .querySelectorAll(":scope > .is-open")
        .forEach((sibling) => sibling.classList.remove("is-open"));

      item.classList.add("is-open");
    });

    // Tapping anywhere outside the nav closes whatever's currently open —
    // otherwise a menu opened this way would stay open until a 2nd tap on
    // its own trigger, unlike hover which closes itself once the pointer
    // moves away.
    document.addEventListener("click", (event) => {
      if (!nav.contains(event.target)) {
        nav
          .querySelectorAll(".is-open")
          .forEach((el) => el.classList.remove("is-open"));
      }
    });
  }
}
