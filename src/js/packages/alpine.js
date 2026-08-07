import Alpine from "alpinejs";

window.Alpine = Alpine;

// Components must be registered before start(), or the markup using them
// initialises against an undefined x-data.

Alpine.start();
