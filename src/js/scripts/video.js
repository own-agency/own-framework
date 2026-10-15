// Registers the elements inc/functions/video-player.php wraps around every
// core/video with controls. The Dutch pack is registered up front rather than
// lazy-loaded, so the labels are Dutch on first paint instead of switching
// over from English.
import "@videojs/html/i18n";
import "@videojs/html/i18n/locales/nl/register";
import "@videojs/html/video/player";
import "@videojs/html/video/neutral-skin";
