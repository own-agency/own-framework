// Registers the elements inc/functions/video-player.php renders around every
// core/video with controls. The Dutch pack is registered up front rather than
// lazy-loaded, so the labels are Dutch on first paint instead of switching
// over from English.
//
// "neutral-skin" is imported for its registrations (every UI element and
// icon the neutral layout uses), not for <video-neutral-skin> itself: the
// theme renders that layout in the light DOM instead (see video-player.php).
import "@videojs/html/i18n";
import "@videojs/html/i18n/locales/nl/register";
import "@videojs/html/video/player";
import "@videojs/html/video/neutral-skin";
