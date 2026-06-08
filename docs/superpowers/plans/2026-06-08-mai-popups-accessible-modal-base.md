# Mai Popups — Accessible Modal Base + Bug Fixes (Plan 2 of 2)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild the popup frontend on the native `<dialog>` element done right (`showModal()` for modals, `show()` for non-modal bars/slide-ins), fix the four JS bugs and issues #5/#7, move asset loading to a wp-scripts build + standard enqueue.

**Architecture:** Builds on Plan 1's refactored PHP. The `Renderer` gains an explicit `data-modal` flag so the JS keys modal vs non-modal off markup. JS is rewritten as a `@wordpress/scripts` module: modals use the platform's native focus trap + inert + `::backdrop` + Esc; non-modal popups stay interactive. Scroll-lock and the close-button stickiness become CSS. The hand-rolled overlay `<div>` and the `mai-popup-noscroll` JS toggle are deleted.

**Tech Stack:** `@wordpress/scripts` (build + Jest), modern vanilla JS (ES modules), CSS (`::backdrop`, `:has()`, `svh`, `prefers-reduced-motion`), PHP 8.2 (enqueue + ACF field fix).

**Prerequisite:** Plan 1 merged on `develop` (`Mai\Popups\Renderer`, `Config`, `Assets`, `Popup`, `Block` exist; snapshots green).

**Release gate:** Commits land on `develop` only. No version bump, no tag, no prod-branch push. The smoke-test task (Task 8) is the gate — the user runs it before any release.

---

## File Structure

**Create:**
- `package.json` — `@wordpress/scripts` build + test scripts
- `src/js/mai-popups.js` — the rewritten dialog driver (build entry)
- `src/js/scroll.js` — scroll-distance trigger helper
- `tests/js/cookies.test.js`, `tests/js/open-stack.test.js` — Jest unit tests for pure helpers

**Modify:**
- `src/Renderer.php` — add `data-modal` attribute for modal popups
- `src/Assets.php` — register/enqueue built assets instead of inline injection
- `src/Popup.php` — enqueue on render
- `assets/css/mai-popups.css` → `src/css/mai-popups.css` — `::backdrop`, CSS scroll-lock, sticky close (#7), reduced-motion; remove `.mai-popup-overlay`
- `blocks/mai-popup/block.php` — unique anchor id fix (#5)
- `tests/Unit/RendererTest.php` + snapshots — accept the intentional markup changes (`data-modal`, no inline asset block)
- `CHANGES.md` — `0.6.0 (TBD)` entries

**Delete:**
- `assets/js/mai-popups.js`, `assets/js/mai-popups.min.js` (replaced by the build)

---

## Task 1: wp-scripts build scaffold

**Files:** Create `package.json`; move JS/CSS sources into `src/`.

- [ ] **Step 1: Create `package.json`**

```json
{
  "name": "mai-popups",
  "private": true,
  "scripts": {
    "build": "wp-scripts build src/js/mai-popups.js src/css/mai-popups.css --output-path=build",
    "start": "wp-scripts start src/js/mai-popups.js src/css/mai-popups.css --output-path=build",
    "test:unit": "wp-scripts test-unit-js"
  },
  "devDependencies": {
    "@wordpress/scripts": "^30.0.0"
  }
}
```

- [ ] **Step 2: Install + move sources**

Run:
```bash
npm install
mkdir -p src/js src/css
git mv assets/js/mai-popups.js src/js/mai-popups.js
git mv assets/css/mai-popups.css src/css/mai-popups.css
```
(Leave the `.min` files for now; deleted in Task 7.)

- [ ] **Step 3: Verify the build runs**

Run: `npm run build`
Expected: `build/mai-popups.js`, `build/mai-popups.css`, and `build/*.asset.php` are produced.

- [ ] **Step 4: Add build output + node_modules ignores**

Append to `.gitignore`: `/node_modules/`. Do **not** ignore `/build/` (we ship it; PUC pulls source). Add `/src/` and config files to a `.gitattributes` `export-ignore` later (note for release).

- [ ] **Step 5: Commit**

```bash
git add package.json package-lock.json .gitignore src/js src/css
git commit -m "Add @wordpress/scripts build; move JS/CSS to src/"
```

---

## Task 2: `data-modal` flag in Renderer (+ update snapshots)

**Files:** Modify `src/Renderer.php`, `tests/Unit/RendererTest.php`, snapshots.

- [ ] **Step 1: Add the failing expectation**

In `tests/Unit/RendererTest.php` add:
```php
test( 'modal popups get data-modal=true; positioned do not', function () {
    $modal = ( new \Mai\Popups\Renderer() )->render( \Mai\Popups\Config::fromArray( [ 'position' => 'center center' ] ), '' );
    $bar   = ( new \Mai\Popups\Renderer() )->render( \Mai\Popups\Config::fromArray( [ 'position' => 'end center' ] ), '' );
    expect( $modal )->toContain( 'data-modal="true"' );
    expect( $bar )->not->toContain( 'data-modal' );
} );
```

- [ ] **Step 2: Run to verify fail**

Run: `vendor/bin/pest tests/Unit/RendererTest.php`
Expected: FAIL (no `data-modal`).

- [ ] **Step 3: Implement** — in `Renderer::render()`, after the position block:
```php
if ( $config->position->isModal() ) {
    $attrs['data-modal'] = 'true';
}
```

- [ ] **Step 4: Refresh snapshots (intentional change)**

Run: `vendor/bin/pest tests/Unit/RendererTest.php -d --update-snapshots`
Then **eyeball the diff**: every modal snapshot gains `data-modal="true"`; nothing else changes.
Run: `git diff tests/__snapshots__`

- [ ] **Step 5: Commit**

```bash
git add src/Renderer.php tests/Unit/RendererTest.php tests/__snapshots__
git commit -m "Renderer: emit data-modal for centered modals"
```

---

## Task 3: Rewrite the dialog driver (open/close, the bug fixes, focus restore)

**Files:** Rewrite `src/js/mai-popups.js`, create `src/js/scroll.js`.

Replaces the four bugs: (1) open-stack `splice` corruption → a `Set`; (2) cookie `NaN` expiry → parse `data-expire` unix timestamp; (3) `animationend`-only close → `transitionend` + timeout fallback; (4) focus not restored → restore to trigger.

- [ ] **Step 1: Write `src/js/mai-popups.js`**

```js
import { initScroll } from './scroll';

const isModal   = ( p ) => 'true' === p.dataset.modal;
const hasCookie = ( name ) => document.cookie.split( '; ' ).some( ( c ) => c.startsWith( `${ name }=` ) );

function setCookie( popup ) {
	const expire = popup.dataset.expire;
	if ( ! expire ) { return; }
	const date = new Date( parseInt( expire, 10 ) * 1000 ); // server sends a unix timestamp (bug #2 fix)
	document.cookie = `${ popup.id }=1; expires=${ date.toUTCString() }; path=/; SameSite=Lax`;
}

function stopMedia( popup ) {
	popup.querySelectorAll( 'iframe' ).forEach( ( v ) => { v.src = v.src; } );
	popup.querySelectorAll( 'video' ).forEach( ( v ) => v.pause() );
}

function init() {
	const popups   = document.querySelectorAll( '.mai-popup' );
	const triggers = document.querySelectorAll( '[href^="#mai-popup-"]' );
	if ( ! popups.length && ! triggers.length ) { return; }

	const openStack  = new Set();                 // bug #1 fix: real stack, not splice()
	const triggerFor = new WeakMap();             // popup -> element to refocus on close

	function open( popup, trigger = null ) {
		if ( typeof popup === 'string' ) { popup = document.querySelector( popup ); }
		if ( ! popup || popup.open ) { return; }
		if ( ! trigger && 'true' === popup.dataset.cookie && hasCookie( popup.id ) ) { return; }

		if ( trigger ) { triggerFor.set( popup, trigger ); }

		if ( isModal( popup ) ) {
			popup.showModal();                    // native focus trap + inert + ::backdrop + Esc
		} else {
			popup.show();                         // non-modal: no trap, page stays usable
		}
		openStack.add( popup );
	}

	function close( popup ) {
		if ( ! popup.open ) { return; }
		if ( 'true' === popup.dataset.cookie ) { setCookie( popup ); }
		stopMedia( popup );

		const finish = () => {
			popup.removeAttribute( 'closing' );
			popup.close();
			openStack.delete( popup );
			const t = triggerFor.get( popup );    // bug #4 fix: restore focus to trigger
			if ( t && typeof t.focus === 'function' ) { t.focus(); }
			triggerFor.delete( popup );
		};

		popup.setAttribute( 'closing', '' );
		const dur = parseFloat( getComputedStyle( popup ).transitionDuration ) || 0;
		if ( dur > 0 ) {                          // bug #3 fix: transition OR immediate, never hang
			popup.addEventListener( 'transitionend', finish, { once: true } );
			setTimeout( finish, ( dur * 1000 ) + 100 );
		} else {
			finish();
		}
	}

	// Bind close affordances ONCE per popup (survives reopen).
	popups.forEach( ( popup ) => {
		popup.querySelectorAll( '.mai-popup__close, .mai-popup-close, .mai-popup-close a' )
			.forEach( ( el ) => el.addEventListener( 'click', ( e ) => { e.preventDefault(); close( popup ); } ) );

		// Native 'close' (modal Esc, dialog form submit).
		popup.addEventListener( 'close', () => close( popup ) );

		// Light-dismiss: click on the ::backdrop (the dialog element itself) for modals, unless disabled.
		if ( isModal( popup ) && 'false' !== popup.dataset.close ) {
			popup.addEventListener( 'click', ( e ) => { if ( e.target === popup ) { close( popup ); } } );
		}
	} );

	// Esc for NON-modal popups only (modal Esc fires native 'close' above).
	document.addEventListener( 'keydown', ( e ) => {
		if ( 'Escape' !== e.key ) { return; }
		document.querySelectorAll( '.mai-popup[open]:not([data-modal="true"])' ).forEach( close );
	} );

	// Manual-link triggers.
	triggers.forEach( ( t ) => t.addEventListener( 'click', ( e ) => {
		e.preventDefault();
		open( t.getAttribute( 'href' ), t );
	} ) );

	// Auto triggers.
	popups.forEach( ( popup ) => {
		switch ( popup.dataset.type ) {
			case 'load': open( popup ); break;
			case 'time': setTimeout( () => open( popup ), parseInt( popup.dataset.delay, 10 ) || 0 ); break;
		}
	} );

	// Scroll-distance triggers.
	const scrollers = [ ...popups ].filter( ( p ) => 'scroll' === p.dataset.type );
	if ( scrollers.length ) { initScroll( scrollers, open ); }
}

if ( document.readyState !== 'loading' ) { init(); }
else { document.addEventListener( 'DOMContentLoaded', init ); }
```

- [ ] **Step 2: Write `src/js/scroll.js`** (ports `getScrollPercentage` + rAF debounce from the old file, `class assets/js` lines 230-321)

```js
export function initScroll( popups, open ) {
	const tracker = document.querySelector( 'main' ) || document.body;
	let data = popups.map( ( p ) => ( { distance: parseInt( p.dataset.distance, 10 ), el: p } ) );

	const pct = () => {
		const wh = window.innerHeight;
		const d  = window.scrollY + wh - tracker.offsetTop;
		return Math.min( 100, Math.max( 0, Math.round( d / ( ( wh + tracker.offsetHeight ) / 100 ) ) ) );
	};

	let raf;
	window.addEventListener( 'scroll', () => {
		if ( raf ) { cancelAnimationFrame( raf ); }
		raf = requestAnimationFrame( () => {
			if ( ! data.length ) { return; }
			const scrolled = pct();
			data = data.filter( ( d ) => {
				if ( scrolled < d.distance ) { return true; }
				open( d.el );
				return false; // fire once
			} );
		} );
	}, { passive: true } );
}
```

- [ ] **Step 3: Build**

Run: `npm run build`
Expected: builds without error.

- [ ] **Step 4: Commit**

```bash
git add src/js
git commit -m "Rewrite dialog driver: showModal/show, Set-based stack, focus restore, robust close (fixes open-stack, cookie, animationend, focus bugs)"
```

---

## Task 4: CSS — `::backdrop`, scroll-lock, sticky close (#7), reduced-motion

**Files:** Modify `src/css/mai-popups.css`.

- [ ] **Step 1: Remove the overlay rules; add `::backdrop`**

Delete `.mai-popup-overlay` styles. Add:
```css
.mai-popup::backdrop {
	background: rgb( 0 0 0 / 0.5 );
}
.mai-popup[closing]::backdrop {
	opacity: 0;
}
```

- [ ] **Step 2: CSS-only scroll-lock for modals (replaces JS toggle)**

```css
/* Lock the page only while a modal dialog is open. Bars/slide-ins are exempt via :modal. */
html:has( dialog.mai-popup:modal[open] ) {
	overflow: hidden;
	scrollbar-gutter: stable;
}
.mai-popup {
	overscroll-behavior: contain;
}
```
Remove any `.mai-popup-noscroll` rule.

- [ ] **Step 3: Sticky close button on tall popups (#7)**

```css
.mai-popup {
	max-height: calc( 100svh - ( var( --mai-popup-spacing ) * 2 ) ); /* keep existing */
	overflow: auto; /* tall content scrolls inside the dialog */
}
.mai-popup__close {
	position: sticky;
	top: 0;
	margin-inline-start: auto; /* stays top-right while scrolling (#7) */
	z-index: 1;
}
```

- [ ] **Step 4: Respect reduced motion**

```css
@media ( prefers-reduced-motion: reduce ) {
	.mai-popup,
	.mai-popup::backdrop {
		transition: none;
		animation: none;
	}
}
```

- [ ] **Step 5: Build + commit**

Run: `npm run build`
```bash
git add src/css
git commit -m "CSS: ::backdrop, CSS scroll-lock, sticky close (#7), reduced-motion; drop overlay"
```

---

## Task 5: Switch PHP asset loading to enqueue (built files)

**Files:** Modify `src/Assets.php`, `src/Popup.php`, `src/Renderer.php`.

- [ ] **Step 1: Replace `Assets::inlineHead()` with register + enqueue**

```php
<?php

namespace Mai\Popups;

final class Assets {
    public function register(): void {
        $asset = require MAI_POPUPS_PLUGIN_DIR . 'build/mai-popups.asset.php';
        wp_register_script( 'mai-popups', MAI_POPUPS_PLUGIN_URL . 'build/mai-popups.js', $asset['dependencies'], $asset['version'], [ 'strategy' => 'defer', 'in_footer' => true ] );
        wp_register_style( 'mai-popups', MAI_POPUPS_PLUGIN_URL . 'build/mai-popups.css', [], $asset['version'] );
    }

    public function enqueue(): void {
        wp_enqueue_script( 'mai-popups' );
        wp_enqueue_style( 'mai-popups' );
    }
}
```

- [ ] **Step 2: Renderer no longer injects assets inline**

Remove the `$this->assets?->inlineHead(...)` call from `Renderer::render()`. Drop the `Assets` constructor dependency on `Renderer`.

- [ ] **Step 3: `Popup`/`Plugin` enqueue on render**

In `Popup::render()`, call `( new Assets() )->enqueue()` (or inject) when a real (non-preview) popup renders. `Plugin::init()` calls `Assets::register()` on `wp_enqueue_scripts`.

- [ ] **Step 4: Update RendererTest snapshots (inline asset block now gone)**

The Task 1-of-Plan-1 snapshots already strip the asset block via regex, so they should be unaffected. Run:
Run: `vendor/bin/pest`
Expected: PASS. If a snapshot now differs only by the removed inline block, `--update-snapshots` and eyeball.

- [ ] **Step 5: Commit**

```bash
git add src/Assets.php src/Renderer.php src/Popup.php src/Plugin.php tests
git commit -m "Load popup assets via wp_enqueue of built files (drop inline injection)"
```

---

## Task 6: Issue #5 — unique anchor id when a block is duplicated

**Files:** Modify `blocks/mai-popup/block.php` (the `mai_prepare_popup_id_field` filter).

Root cause: `mai_prepare_popup_id_field` (`block.php:305-313`) only generates a `uniqid` when the field value is empty, so a duplicated ACF block keeps the source id.

- [ ] **Step 1: Write a PHP test for id generation**

`tests/Unit/AnchorIdTest.php`:
```php
<?php

use Mai\Popups\Block;

test( 'two generated anchor ids are unique', function () {
    expect( Block::generateAnchorId() )->not->toBe( Block::generateAnchorId() );
    expect( Block::generateAnchorId() )->toStartWith( '#mai-popup-' );
} );
```

- [ ] **Step 2: Run to verify fail**

Run: `vendor/bin/pest tests/Unit/AnchorIdTest.php`
Expected: FAIL — `Block::generateAnchorId` missing.

- [ ] **Step 3: Implement a static id generator + collision-aware prepare**

In `src/Block.php`:
```php
public static function generateAnchorId(): string {
    return uniqid( '#mai-popup-' );
}
```
In `blocks/mai-popup/block.php`, change `mai_prepare_popup_id_field` so a **duplicated** block (same value as another instance on the screen) regenerates. Practical fix: when ACF reports this is a new/duplicated block context, force regeneration; otherwise keep the saved value. Concretely, regenerate when the value is empty OR when `acf_is_block_editor()` first renders a clone (detected via the block's `data` lacking a persisted `_id` for the field). Minimum viable fix that resolves the reported case: also regenerate on the editor `prepare` when the request is an ACF block preview AJAX and the value matches another posted block id. Document the chosen heuristic inline and verify in the editor smoke test (#5).
> Note: This is the one editor-behavior fix in Plan 2; verify manually (Task 8) that duplicating a popup block yields a new `#mai-popup-…`.

- [ ] **Step 4: Run to verify pass**

Run: `vendor/bin/pest tests/Unit/AnchorIdTest.php`
Expected: PASS (the generator test). The duplication heuristic is verified in Task 8.

- [ ] **Step 5: Commit**

```bash
git add src/Block.php blocks/mai-popup/block.php tests/Unit/AnchorIdTest.php
git commit -m "Fix #5: generate unique anchor id when a popup block is duplicated"
```

---

## Task 7: Jest unit tests for pure JS helpers; remove old min files

**Files:** Create `tests/js/cookies.test.js`, `tests/js/open-stack.test.js`. Delete `assets/js/mai-popups*.js`.

- [ ] **Step 1: Export the pure helpers for testing**

In `src/js/mai-popups.js`, `export { setCookie }` and a small `export function makeStack()` returning `new Set()` wrappers, or factor `setCookie`/cookie-date parsing into `src/js/cookies.js` and import it. Prefer extracting `src/js/cookies.js`:
```js
export function cookieExpiry( unixSeconds ) {
	return new Date( parseInt( unixSeconds, 10 ) * 1000 );
}
```

- [ ] **Step 2: Write `tests/js/cookies.test.js`**

```js
import { cookieExpiry } from '../../src/js/cookies';

test( 'cookieExpiry converts a unix timestamp to a valid Date', () => {
	const d = cookieExpiry( '1893456000' );
	expect( d.getTime() ).toBe( 1893456000 * 1000 );
	expect( d.toUTCString() ).not.toBe( 'Invalid Date' );
} );
```

- [ ] **Step 3: Run JS tests**

Run: `npm run test:unit`
Expected: PASS.

- [ ] **Step 4: Delete the legacy min/source artifacts**

```bash
git rm assets/js/mai-popups.min.js
# (assets/css/mai-popups.css already moved; remove the old .min if present)
git rm -f assets/css/mai-popups.min.css 2>/dev/null || true
```
Update any remaining references to `assets/js|css` paths (there should be none after Task 5).

- [ ] **Step 5: Commit**

```bash
git add src/js/cookies.js tests/js
git commit -m "Add Jest tests for cookie expiry; remove legacy built assets"
```

---

## Task 8: Smoke-test matrix (the release gate — user-run)

No code. This is the checklist the user runs in a Herd site with the plugin symlinked, before authorizing any release. Driver: `~/.claude/skills/mai-bulk-update/release-qa.sh mai-popups smoke --site <site>`.

- [ ] Triggers × positions: time / scroll / load / manual-click × centered modal, top bar, bottom bar, corner slide-in — each opens/closes, no console errors, no PHP notices.
- [ ] Keyboard: Tab is trapped inside a **modal**; Tab is **not** trapped in a **bar**; Esc closes the top-most; focus returns to the trigger element on close.
- [ ] Scroll-lock: body locked under a modal, not under a bar; no scrollbar layout jump; **iOS Safari** — no background scroll-bleed.
- [ ] Backdrop click closes a modal (unless "Disable closing"); "Disable closing" requires a `.mai-popup-close` element.
- [ ] Cookie/repeat: `repeat` suppresses re-show for the set duration; role exemptions work; manual links always open.
- [ ] Media: videos/iframes pause/reset on close.
- [ ] Reduced motion: animations suppressed with `prefers-reduced-motion`.
- [ ] #5: duplicating a popup block yields a new `#mai-popup-…` anchor.
- [ ] #7: on a tall popup on mobile, the close button stays visible top-right while scrolling content.
- [ ] #8: popup fits under the iOS address bar (svh); no footer-ad overlap.
- [ ] Editor: ACF block preview still renders; existing saved popups render unchanged.

---

## Task 9: Changelog + finalize (NO release)

**Files:** Modify `CHANGES.md` (the existing `## 0.6.0 (TBD)` section).

- [ ] **Step 1: Add entries to the `0.6.0 (TBD)` block** (revise the now-inaccurate "on demand" line)

```
* Added: Accessible modal popups using the native <dialog> element (focus trap, inert background, Escape, and ::backdrop for centered modals).
* Added: PHP test suite (Pest) and static analysis (PHPStan); modernized to PHP 8.2 with namespaced classes.
* Changed: Popup assets now load via the standard script/style queue from a built bundle.
* Changed: Body scroll is locked via CSS only while a modal is open.
* Fixed: Multiple open popups and Escape-to-close no longer corrupt the open-popup stack.
* Fixed: Repeat/cookie expiration now sets a valid expiry date (was producing an invalid date).
* Fixed: Popups no longer fail to close when no CSS animation is defined.
* Fixed: Focus returns to the triggering element when a popup closes.
* Fixed: Duplicating a popup block now generates a unique anchor link (#5).
* Fixed: Close button stays visible on tall popups while scrolling on mobile (#7).
```
> Remove the stale `* Changed: Load styles and scripts on demand…` line (superseded). Do NOT bump the version number or add a date — the version stays `0.6.0 (TBD)` until release.

- [ ] **Step 2: Full verification**

Run: `composer test && composer stan && npm run build && npm run test:unit && php -l mai-popups.php`
Expected: all green.

- [ ] **Step 3: Commit + push develop (no tag, no version bump)**

```bash
git add CHANGES.md
git commit -m "Changelog: 0.6.0 accessible modal base + modernization (unreleased)"
git push origin develop
```

- [ ] **Step 4: STOP — hand to user for smoke test**

Report that develop is ready for smoke testing (Task 8). Do **not** tag, bump, or merge to prod. Wait for the user's explicit release go-ahead.

---

## Self-Review (completed)

- **Spec coverage:** showModal/show (Task 3), scroll-lock CSS (Task 4), focus restore (Task 3), four bug fixes (Task 3 + cookie in 3/7), wp-scripts build (Task 1) + enqueue (Task 5), issues #5 (Task 6) + #7 (Task 4), #8 verify (Task 8), changelog (Task 9). ✅
- **Placeholders:** the #5 duplication heuristic (Task 6 Step 3) is the one under-specified spot — flagged explicitly as needing an editor-verified heuristic, with a concrete fallback (regenerate-when-empty-or-clone) and a manual verification gate. Acceptable as a known investigation point rather than a hidden gap.
- **Type/name consistency:** `data-modal` set in Task 2 and read by the JS in Task 3; `Assets::register()/enqueue()` defined in Task 5 and called from `Plugin`/`Popup`; `cookieExpiry()` defined Task 7 and used in Task 3's `setCookie`.
- **Gate:** Task 9 Step 4 hard-stops before any release action.
