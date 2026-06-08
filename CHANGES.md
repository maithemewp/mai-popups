# Changelog

## 0.6.0 (TBD)
* Added: Accessible modal popups using the native `<dialog>` element (native focus trap, inert background, Escape to close, and `::backdrop` for centered modals).
* Added: New "Disable closing" setting to disable the close button and disable clicking the overlay to close. This requires a link or button with `mai-popup-close` class in order to close the popup.
* Added: PHP test suite (Pest) and static analysis (PHPStan); modernized to PHP 8.2 with namespaced classes.
* Added: Screen readers now get an accessible name for each popup (from its first heading), and focus moves into the dialog when it opens.
* Changed: Popup styles and scripts now load via the standard WordPress script/style queue from a built bundle.
* Changed: Hardened popup attribute output escaping; the repeat cookie now uses the Secure flag over HTTPS.
* Changed: Body scroll is now locked via CSS only while a modal popup is open.
* Changed: Always load popups, even if cookied, so manual links work.
* Changed: Removed unecessary `render_block` filter.
* Fixed: Multiple open popups and Escape-to-close no longer corrupt the open-popup tracking.
* Fixed: Repeat/cookie expiration now sets a valid expiry date (it was previously producing an invalid date).
* Fixed: Popups no longer fail to close when no CSS animation is defined.
* Fixed: Focus returns to the triggering element when a popup closes.
* Fixed: Duplicating a popup block now generates a unique anchor link (#5).
* Fixed: Close button stays visible on tall popups while scrolling on mobile (#7).
* Fixed: The close button no longer submits a form placed inside the popup.
* Fixed: Scroll-triggered popups now fire reliably on short pages and on themes without a `<main>` element.
* Fixed: Manually opening a popup via a link no longer suppresses its later automatic display.
* Fixed: Timed/Scroll popups were not available after closed (cookied) even if it's set to manually launch via a manual link.

## 0.5.3 (12/21/23)
* Fixed: Distance was value removing trailing zero and using wrong value.

## 0.5.2 (11/28/23)
* Fixed: Compatibility with WP 6.4, changing has-link- to has-links- class name to avoid conflict.
* Fixed: PHP warning if distance or delay is null.

## 0.5.1 (11/27/23)
* Changed: Updated the updater.

## 0.5.0
* Added: Background and text color settings.
* Added: Padding setting to control spacing around content without relying on a nested Group block.
* Changed: Updated the updater.
* Changed: Popup defaults are now used to populate block setting defaults.
* Fixed: Popup markup was occasionally being duplicated in the page source in some configurations.

## 0.4.1
* Fixed: Caching was allowing popups to repeat even though they were already viewed/closed.

## 0.4.0
* Added: New setting to always repeat popup for roles, regardless of repeat/cookie setting.
* Changed: Cookie from SameSite 'Strict' to 'Lax'.
* Fixed: Shortcodes not parsing in block content.
* Fixed: Deprecated required parameter after not required param in function.

## 0.3.5
* Fixed: Editor placeholder element when adding new block.

## 0.3.4
* Fixed: Allow custom classes on popups.

## 0.3.3
* Fixed: Triggered popups not launching if link has nested HTML.
* Fixed: Double slash in asset urls.

## 0.3.2
* Fixed: Popup taller than screen on mobile in some instances.

## 0.3.1
* Fixed: Popup wider than screen on mobile in some instances.

## 0.3.0
* Changed: Converted to `<dialog>` element for native functionality and better accessibility.

## 0.2.3
* Added: Allow any popup to be closed with mai-popup-close class on any element inside the popup.

## 0.2.2
Initial beta release, ready for testing.
