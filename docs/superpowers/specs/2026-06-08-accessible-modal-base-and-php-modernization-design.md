# Mai Popups — Accessible Modal Base + PHP Modernization (Phase 1)

- **Status:** Draft for review · **Date:** 2026-06-08 · **Target release:** 0.6.0
- **Scope:** `mai-popups` only. ACF block kept. Phase 2 (native block / standalone) deferred.

> 🚫 **Release gate:** Nothing is tagged, pushed to a prod branch, or released until the user has personally smoke-tested and explicitly says to release. Local branch work / `develop` commits are fine when asked; the release act waits.

---

## 1. Background & goals

mai-popups renders popups as a native `<dialog>` but opens them with `.show()` (non-modal) and hand-rolls the overlay, Escape, scroll-lock, and focus. That causes real bugs and accessibility gaps. Separately, the PHP is dated (no namespaces, a single `Mai_Popup` class doing five jobs, no tests).

**Goals (one release, 0.6.0):**
1. **Accessible modal base** — rebuild the frontend on the native `<dialog>` the right way; fix the known bugs.
2. **PHP modernization** — PHP 8.2, namespaces/autoload, a clean display architecture, and a test suite.

**Success criteria:** all popup types work across triggers; keyboard + screen-reader behavior is correct (focus trapped in modals, not in bars; Esc closes; focus returns to trigger); the cookie/repeat and multi-popup bugs are gone; tests + PHPStan pass; rendered output is verified by the user via smoke test before any release.

## 2. Non-goals / deferred

- **Phase 2 — ACF → native block, standalone/non-Mai support, content migration.** Deferred. The migration design (compat render-shim + `transforms.from` + opt-in `wp mai-popups migrate --dry-run`) is recorded in the conversation for when it's revisited.
- No new popup *features* (no new triggers/animations). This is a correctness + a11y + internals release.

## 3. Constraints & invariants

- **PHP 8.2 floor** (`Requires PHP: 8.2` in header).
- **ACF block unchanged** — `blocks/mai-popup/block.php` field group and `acf/mai-popup` block name stay; only its render bridge points at the new code.
- **Anchor `id` contract is stable** — the `#mai-popup-xxxx` id lives in users' link/button content and must keep working as a trigger. (HTML/CSS *classes/structure* may change — user confirmed no site-specific custom CSS is expected — but this id contract holds.)
- **Public API preserved** — keep `mai_do_popup()` and `maipopups_get_defaults()` as supported, first-class procedural wrappers (template tags; NOT deprecated) over the new classes, and preserve the `mai_popup_default_args` filter.
- **Reduced motion** — honor `prefers-reduced-motion`.

## 4. Workstream A — Accessible modal base (frontend)

### 4.1 Open behavior (by type)
- **Centered modal** (`vertical=center` & `horizontal=center`): `dialog.showModal()` → native focus trap, inert background, Esc, and `::backdrop`. **Delete** the hand-built `.mai-popup-overlay` div; style `::backdrop` instead.
- **Non-modal** (any positioned variant — top/bottom bars, corner slide-ins): `dialog.show()` → non-modal, no focus trap (correct). Page stays usable.

### 4.2 Close lifecycle
- Drive close off the dialog `close` event + CSS transitions; **remove the `animationend` dependency** (a missing animation currently leaves the popup never closing). Use a transition/timeout fallback.
- Native Esc handles modals. Keep an explicit key handler only where needed for non-modal variants.

### 4.3 Scroll lock (modals only)
- Pure CSS: `html:has(dialog:modal[open]) { overflow: hidden; scrollbar-gutter: stable; }` plus `overscroll-behavior: contain`. `:modal` exempts bars automatically. **Delete** the JS `mai-popup-noscroll` class toggle.
- **iOS Safari scroll-bleed** is a known risk → explicit smoke-test item; JS `position:fixed` fallback held in reserve.

### 4.4 Focus
- `showModal()` provides the trap + initial focus. Add **focus restoration to the triggering element** on close. Non-modal bars do not steal/trap focus.

### 4.5 Bug fixes (the four)
1. **Open-stack corruption** — replace `open = open.splice(...)` with correct removal (a `Set`, or `splice` for side-effect only). Fixes Esc-closes-last + multi-popup tracking.
2. **Cookie expiry** — server already emits `data-expire` as a unix timestamp (`strtotime()`); JS must build the cookie from it (`new Date(parseInt(expire, 10) * 1000)`), not from an empty `Date`/`parseInt(Date)` (`NaN`). Fixes `repeat`/expiry (relates to **#5**).
3. **animationend close** — see 4.2.
4. **Focus restore** — see 4.4.

### 4.6 Build & asset loading
- Add a **wp-scripts** build (`package.json`, `@wordpress/scripts`) bundling/minifying the popup JS (and CSS), replacing the committed `.min.js`.
- Move from the current inline first-instance `<link>`/`<script>` injection (`get_scripts_styles()`) to standard `wp_enqueue_style/script` registered up front and enqueued when a popup renders. Keep `defer`. (Sidesteps the on-demand-CSS class of bug seen in mai-lists #3.)

## 5. Workstream B — PHP modernization

### 5.1 Namespace, autoload, layout
- Namespace `Mai\Popups\`; PSR-4 `"autoload": { "psr-4": { "Mai\\Popups\\": "src/" } }`; `composer dump-autoload`. Code moves to `src/`.

### 5.2 Decompose `Mai_Popup` (the "display")
Current `Mai_Popup` does sanitize+model, markup, asset injection, cookie/repeat, footer-timing, conditions. Split into single-purpose units:

| Unit | Responsibility |
|---|---|
| `Plugin` | bootstrap, hooks, updater wiring (from `mai-popups.php`) |
| `Config` (readonly) | parsed + sanitized popup config (value object) |
| `Enum\Trigger` | `Manual` / `Load` / `Scroll` / `Time` |
| `Enum\Animation` | `Fade` / `Up` / `Down` |
| `Enum\Alignment` | vertical/horizontal → `start`/`center`/`end` mapping (incl. modal detection) |
| `Renderer` | builds the `<dialog>` markup from `Config` + content |
| `Cookies` | `use_cookie()` role logic + `data-expire` value |
| `Conditions` | the callable/boolean `condition` gate |
| `Assets` | register/enqueue built CSS+JS |
| `Block` | ACF block registration bridge (render_callback → `Renderer`) |
| `Defaults` | defaults + `mai_popup_default_args` filter |

`Renderer` exposes a `modal` concept (`Alignment::isModal()`) so server-rendered class/attr hints and the JS agree on modal vs non-modal.

### 5.3 PHP 8.2 idioms
Typed properties, constructor property promotion, `readonly` on the `Config`/value objects, native `enum`s (above), `match` over the trigger/position switches, named args. Strict types where practical.

### 5.4 Backward compatibility
- `mai_do_popup($args, $content)` and `maipopups_get_defaults()` remain as **supported procedural wrappers** (template tags, not deprecated) delegating to the new classes. Preserve the `mai_popup_default_args` filter exactly.
- ACF block name (`acf/mai-popup`), field keys/names, and saved data are untouched. `block.php`'s render callback delegates to `Renderer`.

## 6. Workstream C — Tests & tooling

- **Pest + Brain Monkey (+ Mockery)** for fast unit tests with no WP bootstrap; **PHPStan** (with WordPress stubs) for static analysis. wp-env integration tests are out of scope unless the ACF path proves it needs them.
- **What's covered:**
  - **Render snapshots** of `Renderer` output across `Trigger × Alignment × Animation` (golden fixtures) — regression guard for markup/attribute contracts.
  - **Cookie/expire**: `Cookies::use_cookie()` role-exemption logic; `data-expire` timestamp generation.
  - **Enums & mapping**: alignment → start/center/end, modal detection.
  - **Defaults + filter**: `mai_popup_default_args` applied; sanitization of each field.
- **Tooling:** `composer test`, `composer stan` scripts; `@wordpress/scripts` for JS build (`npm run build`); PHPStan config committed. GitHub Actions CI is optional (note, not required for this release).

## 7. Backward-compatibility summary

Existing popups (ACF blocks) render unchanged in behavior; their anchor ids, triggers, cookies/repeat, colors, padding, width, and position all keep working. Markup classes may be refined but the public id contract and ACF data are preserved. Public PHP functions + filter keep working via shims.

## 8. QA / smoke-test matrix (the release gate)

User-run before any release. Each: no console errors, no PHP notices.
- **Triggers × positions:** time / scroll / load / click(manual) × centered modal, top bar, bottom bar, corner slide-in.
- **Keyboard:** Tab is trapped inside a modal; Tab is **not** trapped in a bar; Esc closes the top-most; focus returns to the trigger on close.
- **Scroll lock:** body locked under a modal, not under a bar; no scrollbar layout jump; **iOS Safari** no background scroll-bleed.
- **Cookie/repeat:** `repeat` suppresses re-show for the set duration; role exemptions work; manual links still open regardless.
- **Media:** videos/iframes pause/reset on close.
- **Reduced motion:** animations suppressed when `prefers-reduced-motion`.
- **Editor:** ACF block preview still renders; saving an existing popup doesn't change its output unexpectedly.
- **Issue regressions:** unique anchor id when a popup block is duplicated (#5); close button stays put on a tall scrolling popup on mobile (#7); popup fits under the iOS address bar with no footer-ad overlap (#8).

## 9. Rollout

Land on `develop` as 0.6.0 work (first-ever tag for this plugin). **Do not tag/merge/release** until the smoke-test matrix passes by the user and they say go. Confirm PUC tag-based model before the first tag.

## 10. Open issues folded in

| Issue | Status | Plan |
|---|---|---|
| **#3** — Hide close button | **Done on develop** | `disable_close` ("Disable closing") already implements this (hides the button, requires your own `.mai-popup-close`); ships in 0.6.0. Comment + close on release per convention. |
| **#5** — Duplicate anchor id w/ multiple popups | **Open bug → Phase 1** | ACF block duplication keeps the same `#mai-popup-xxxx` because `mai_prepare_popup_id_field` only generates an id when empty. Fix: guarantee a unique id per instance (regenerate on duplicate / resolve collisions on save). ACF field-prepare logic only; no data-shape change, anchor-id contract preserved for existing single popups. |
| **#7** — Sticky close on tall mobile popups | **→ Phase 1** | Make the close button sticky/fixed within scrollable popups (CSS). |
| **#8** — Mobile height / iOS address bar / footer ads | **Mostly on develop** | `max-height: calc(100svh - …)` already handles the iOS address-bar case. Verify in smoke test; address residual footer-ad overlap (z-index vs Mai Publisher) only if it reproduces. |

## 11. Open questions

- CI (GitHub Actions) now or later? (Assumed: later — local `composer test`/`stan` for this release.)
- Exact new namespace root confirmed as `Mai\Popups\`? (Assumed yes.)
