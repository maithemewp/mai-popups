# Changelog

## 0.6.0 (TBD)
* Added: New "Disable closing" setting to disable the close button and disable clicking the overlay to close. This requires a link or button with `mai-popup-close` class in order to close the popup.
* Changed: Always load popups, even if cookied, so manual links work.
* Changed: Load styles and scripts on demand so they are always available no matter where/when the popup is loaded.
* Changed: Removed unecessary `render_block` filter.
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
