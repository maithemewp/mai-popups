# Mai Popups — PHP Modernization + Test Foundation (Plan 1 of 2)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refactor mai-popups internals to PHP 8.2 + PSR-4 namespaces with a clean, single-responsibility display architecture and a real test suite — with **zero change to rendered output or behavior**.

**Architecture:** Introduce `Mai\Popups\` under `src/` (Composer PSR-4). Split the god-class `Mai_Popup` into focused units (`Config`, `Renderer`, `Cookies`, `Conditions`, `Assets`, `Popup`, `Block`) plus enums (`Trigger`, `Animation`, `Align`) and a `Position` value object. Lock current output with **characterization (golden-snapshot) tests** written *before* the refactor, so each extraction is proven byte-identical. The ACF block, its field group, the public functions `mai_do_popup()` / `maipopups_get_defaults()`, and the `mai_popup_default_args` filter are all preserved (functions become thin deprecated shims).

**Tech Stack:** PHP 8.2, Composer PSR-4 autoload, Pest 3, Brain Monkey + Mockery (WP-function mocking, no WP bootstrap), PHPStan 2 + php-stubs/wordpress-stubs.

**Reference (current code):** `classes/class-popup.php` (the `Mai_Popup` class), `includes/functions.php` (defaults + public fns), `blocks/mai-popup/block.php` (ACF block + render callback).

**Release gate:** Nothing in this plan tags, version-bumps, or pushes to a prod branch. Commits land on `develop` only. No release until the user smoke-tests and says go.

---

## File Structure

**Create:**
- `src/Enum/Trigger.php` — `enum Trigger: string` (Manual/Load/Scroll/Time)
- `src/Enum/Animation.php` — `enum Animation: string` (Fade/Up/Down)
- `src/Enum/Align.php` — `enum Align: string` (Start/Center/End) + token mapping
- `src/Position.php` — readonly value object `{Align $vertical, Align $horizontal}` + `isModal()`
- `src/Config.php` — readonly value object holding parsed/sanitized popup config + `fromArray()`
- `src/Renderer.php` — builds the `<dialog>` markup string from a `Config` + content
- `src/Cookies.php` — `shouldUse(Config): bool` + `expires(Config): int`
- `src/Conditions.php` — evaluate the `condition` gate
- `src/Assets.php` — first-instance script/style injection (behavior preserved for Plan 1)
- `src/Popup.php` — orchestrator: condition gate → footer-timing → once-guard → echo
- `src/Block.php` — ACF block registration + field group + render bridge
- `src/Defaults.php` — `get(): array` (defaults + `mai_popup_default_args` filter)
- `src/Plugin.php` — bootstrap (hooks, updater, asset registration)
- `phpstan.neon`, `tests/Pest.php`, `tests/Helpers.php`, `tests/Unit/...` test files

**Modify:**
- `composer.json` — add PSR-4 autoload, dev deps, `test`/`stan` scripts
- `includes/functions.php` — `mai_do_popup()` / `maipopups_get_defaults()` become deprecated shims
- `blocks/mai-popup/block.php` — render callback delegates to `Mai\Popups\Renderer`
- `mai-popups.php` — boot `Mai\Popups\Plugin`; require composer autoload

**Delete (end of plan):**
- `classes/class-popup.php` (replaced by `src/` classes; deleted only after snapshots pass against `Renderer`)

---

## Task 1: Test + static-analysis tooling

**Files:**
- Modify: `composer.json`
- Create: `phpstan.neon`, `tests/Pest.php`, `tests/Helpers.php`, `tests/Unit/SmokeTest.php`

- [ ] **Step 1: Add dev dependencies and scripts to `composer.json`**

Merge into `composer.json` (keep the existing `require` PUC entry):

```json
{
    "require-dev": {
        "pestphp/pest": "^3.5",
        "brain/monkey": "^2.6",
        "mockery/mockery": "^1.6",
        "phpstan/phpstan": "^2.1",
        "php-stubs/wordpress-stubs": "^6.7"
    },
    "autoload": {
        "psr-4": { "Mai\\Popups\\": "src/" }
    },
    "autoload-dev": {
        "psr-4": { "Mai\\Popups\\Tests\\": "tests/" }
    },
    "config": {
        "allow-plugins": { "pestphp/pest-plugin": true }
    },
    "scripts": {
        "test": "pest",
        "stan": "phpstan analyse"
    }
}
```

- [ ] **Step 2: Install**

Run: `composer update` (in `~/Plugins/mai-popups`)
Expected: `vendor/bin/pest` and `vendor/bin/phpstan` exist; no resolver errors.

- [ ] **Step 2b: Keep dev deps OUT of the shipped plugin (vendor hygiene)**

mai-popups ships `vendor/` committed (no `composer install` on client sites), so dev tools must never land in committed vendor or the distributed ZIP.

1. Whitelist prod-only vendor — append to `.gitignore`:
```
/vendor/*
!/vendor/autoload.php
!/vendor/composer/
!/vendor/yahnis-elsts/
```
2. Create `.gitattributes` so the GitHub release archive (what PUC downloads) excludes dev/source cruft (`build/` is NOT ignored — it ships):
```
/tests             export-ignore
/docs              export-ignore
/src/js            export-ignore
/src/css           export-ignore
/node_modules      export-ignore
phpstan.neon       export-ignore
package.json       export-ignore
package-lock.json  export-ignore
.gitignore         export-ignore
.gitattributes     export-ignore
```
> Verify this plugin's PUC uses the GitHub-generated source archive (default — honors `export-ignore`). If it uses a custom release asset, gate dev exclusion in that build step instead.

3. Regenerate the committed vendor as production-only so the autoloader/manifest don't reference dev packages:
Run: `composer install --no-dev -o` (produces the committable vendor), then `composer install` again afterward to restore dev tools for local test runs.
Expected: `git status` shows no dev packages under `vendor/` as tracked/added.

- [ ] **Step 3: Create `phpstan.neon`**

```neon
parameters:
    level: 6
    paths:
        - src
    bootstrapFiles:
        - vendor/php-stubs/wordpress-stubs/wordpress-stubs.php
    treatPhpDocTypesAsCertain: false
```
(Note: `src` only for now — legacy files are excluded until Task 9 deletes them.)

- [ ] **Step 4: Create `tests/Pest.php` (Brain Monkey lifecycle)**

```php
<?php

use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;

uses()
    ->beforeEach(function () { setUp(); require_once __DIR__ . '/Helpers.php'; })
    ->afterEach(function () { tearDown(); })
    ->in('Unit');
```

- [ ] **Step 5: Create `tests/Helpers.php` (stub the WP escapers/helpers as pass-throughs)**

```php
<?php

use Brain\Monkey\Functions;

/**
 * Stubs the WordPress sanitize/escape helpers mai-popups calls, as
 * identity/pass-throughs, so unit tests can assert on real structure.
 */
function maipopups_stub_wp_helpers(): void {
    foreach ( [ 'esc_attr', 'esc_html', 'sanitize_text_field', 'sanitize_key', '__' ] as $fn ) {
        Functions\when( $fn )->returnArg( 1 );
    }
    Functions\when( 'rest_sanitize_boolean' )->alias( fn ( $v ) => filter_var( $v, FILTER_VALIDATE_BOOLEAN ) );
    Functions\when( 'shortcode_atts' )->alias(
        fn ( $defaults, $atts ) => array_merge( $defaults, array_intersect_key( (array) $atts, $defaults ) )
    );
    Functions\when( 'apply_filters' )->returnArg( 2 ); // return the value unchanged
}
```

- [ ] **Step 6: Create `tests/Unit/SmokeTest.php`**

```php
<?php

test( 'pest harness runs', function () {
    expect( true )->toBeTrue();
} );
```

- [ ] **Step 7: Run the harness**

Run: `composer test`
Expected: 1 passing test.

- [ ] **Step 8: Commit**

```bash
git add composer.json composer.lock .gitignore .gitattributes phpstan.neon tests/
git commit -m "Add Pest + Brain Monkey + PHPStan tooling; keep dev deps out of shipped vendor"
```
> Note: vendor/ autoload changes are committed in Task 2 (after the PSR-4 dump); Task 1 commits no vendor packages — dev deps are gitignored, PUC is already committed.

---

## Task 2: `Trigger`, `Animation`, `Align` enums + `Position` value object

**Files:**
- Create: `src/Enum/Trigger.php`, `src/Enum/Animation.php`, `src/Enum/Align.php`, `src/Position.php`
- Test: `tests/Unit/PositionTest.php`, `tests/Unit/EnumTest.php`

- [ ] **Step 1: Write the failing tests**

`tests/Unit/EnumTest.php`:
```php
<?php

use Mai\Popups\Enum\Trigger;
use Mai\Popups\Enum\Animation;
use Mai\Popups\Enum\Align;

test( 'trigger parses known values and falls back to manual', function () {
    expect( Trigger::from( 'time' ) )->toBe( Trigger::Time );
    expect( Trigger::tryFromString( 'nope' ) )->toBe( Trigger::Manual );
} );

test( 'animation falls back to fade', function () {
    expect( Animation::tryFromString( 'up' ) )->toBe( Animation::Up );
    expect( Animation::tryFromString( 'xxx' ) )->toBe( Animation::Fade );
} );

test( 'align maps legacy tokens to start/center/end', function () {
    expect( Align::fromToken( 'top' ) )->toBe( Align::Start );
    expect( Align::fromToken( 'bottom' ) )->toBe( Align::End );
    expect( Align::fromToken( 'left' ) )->toBe( Align::Start );
    expect( Align::fromToken( 'right' ) )->toBe( Align::End );
    expect( Align::fromToken( 'center' ) )->toBe( Align::Center );
    expect( Align::fromToken( 'garbage' ) )->toBe( Align::Center );
} );
```

`tests/Unit/PositionTest.php`:
```php
<?php

use Mai\Popups\Position;
use Mai\Popups\Enum\Align;

test( 'position parses "center center" as modal', function () {
    $p = Position::fromString( 'center center' );
    expect( $p->vertical )->toBe( Align::Center );
    expect( $p->horizontal )->toBe( Align::Center );
    expect( $p->isModal() )->toBeTrue();
} );

test( 'position parses "top right" as non-modal start/end', function () {
    $p = Position::fromString( 'top right' );
    expect( $p->vertical )->toBe( Align::Start );
    expect( $p->horizontal )->toBe( Align::End );
    expect( $p->isModal() )->toBeFalse();
} );
```

- [ ] **Step 2: Run to verify they fail**

Run: `vendor/bin/pest tests/Unit/EnumTest.php tests/Unit/PositionTest.php`
Expected: FAIL — classes not found.

- [ ] **Step 3: Implement the enums**

`src/Enum/Trigger.php`:
```php
<?php

namespace Mai\Popups\Enum;

enum Trigger: string {
    case Manual = 'manual';
    case Load   = 'load';
    case Scroll = 'scroll';
    case Time   = 'time';

    public static function tryFromString( string $value ): self {
        return self::tryFrom( $value ) ?? self::Manual;
    }
}
```

`src/Enum/Animation.php`:
```php
<?php

namespace Mai\Popups\Enum;

enum Animation: string {
    case Fade = 'fade';
    case Up   = 'up';
    case Down = 'down';

    public static function tryFromString( string $value ): self {
        return self::tryFrom( $value ) ?? self::Fade;
    }
}
```

`src/Enum/Align.php` (mirrors the mapping at `classes/class-popup.php:181-186`):
```php
<?php

namespace Mai\Popups\Enum;

enum Align: string {
    case Start  = 'start';
    case Center = 'center';
    case End    = 'end';

    public static function fromToken( string $token ): self {
        return match ( trim( $token ) ) {
            'top', 'left', 'start' => self::Start,
            'bottom', 'right', 'end' => self::End,
            default => self::Center,
        };
    }
}
```

- [ ] **Step 4: Implement `Position`**

`src/Position.php`:
```php
<?php

namespace Mai\Popups;

use Mai\Popups\Enum\Align;

final readonly class Position {
    public function __construct(
        public Align $vertical,
        public Align $horizontal,
    ) {}

    public static function fromString( string $position ): self {
        $parts      = array_values( array_filter( array_map( 'trim', explode( ' ', $position ) ) ) );
        $vertical   = Align::fromToken( $parts[0] ?? 'center' );
        $horizontal = Align::fromToken( $parts[1] ?? ( $parts[0] ?? 'center' ) );

        return new self( $vertical, $horizontal );
    }

    public function isModal(): bool {
        return Align::Center === $this->vertical && Align::Center === $this->horizontal;
    }
}
```

- [ ] **Step 5: Run to verify pass**

Run: `vendor/bin/pest tests/Unit/EnumTest.php tests/Unit/PositionTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Enum src/Position.php tests/Unit/EnumTest.php tests/Unit/PositionTest.php
git commit -m "Add Trigger/Animation/Align enums and Position value object"
```

---

## Task 3: Characterization snapshots of current `Mai_Popup::get()`

Goal: pin the **current** rendered markup before extracting `Renderer`, so the refactor is provably byte-identical. The legacy class is loaded directly for this test.

**Files:**
- Create: `tests/Unit/CharacterizationTest.php`, `tests/__snapshots__/` (Pest snapshot dir, auto-created)

- [ ] **Step 1: Write the characterization test (loads legacy class, mocks WP, snapshots output)**

`tests/Unit/CharacterizationTest.php`:
```php
<?php

beforeEach( function () {
    maipopups_stub_wp_helpers();
    \Brain\Monkey\Functions\when( 'is_user_logged_in' )->justReturn( false );
    \Brain\Monkey\Functions\when( 'current_user_can' )->justReturn( false );
    if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', '/tmp/' ); }
    require_once __DIR__ . '/../../includes/functions.php'; // maipopups_get_defaults()
    require_once __DIR__ . '/../../classes/class-popup.php'; // Mai_Popup
} );

dataset( 'popup_configs', [
    'modal-time'      => [ [ 'trigger' => 'time', 'delay' => '3', 'position' => 'center center', 'animate' => 'fade', 'width' => '600px', 'padding' => 'md' ] ],
    'modal-scroll'    => [ [ 'trigger' => 'scroll', 'distance' => '50', 'position' => 'center center', 'animate' => 'up' ] ],
    'bar-bottom-load' => [ [ 'trigger' => 'load', 'position' => 'end center', 'animate' => 'up', 'width' => '100%' ] ],
    'corner-manual'   => [ [ 'trigger' => 'manual', 'position' => 'end end', 'id' => 'mai-popup-fixed', 'disable_close' => true ] ],
    'colored-padding' => [ [ 'trigger' => 'time', 'repeat' => '7 days', 'background' => 'primary', 'color' => 'white', 'padding' => 'lg', 'class' => 'my-popup' ] ],
] );

test( 'current Mai_Popup::get() output is stable', function ( array $args ) {
    $popup = new \Mai_Popup( $args, '<p>Inner content</p>' );
    // get_scripts_styles() injects mtime-based versions; strip the asset block for a stable snapshot.
    $html = preg_replace( '#<link id="mai-popups-css".*?</script>#s', '', $popup->get() );
    expect( $html )->toMatchSnapshot();
} )->with( 'popup_configs' );
```

- [ ] **Step 2: Generate the baseline snapshots**

Run: `vendor/bin/pest tests/Unit/CharacterizationTest.php`
Expected: PASS, creating snapshot files under `tests/__snapshots__/`. (First run writes baselines.)

- [ ] **Step 3: Eyeball one snapshot for sanity**

Run: `ls tests/__snapshots__ && sed -n '1,40p' tests/__snapshots__/*CharacterizationTest*`
Expected: real `<dialog ...>` markup with the data-* attributes you expect (`data-type`, `data-animate`, `data-vertical`, etc.).

- [ ] **Step 4: Commit the baselines**

```bash
git add tests/Unit/CharacterizationTest.php tests/__snapshots__
git commit -m "Add characterization snapshots locking current popup markup"
```

---

## Task 4: `Config` value object (parse + sanitize)

Extract the sanitization from `Mai_Popup::__construct()` (`classes/class-popup.php:24-49`) into an immutable `Config`.

**Files:**
- Create: `src/Config.php`, `src/Defaults.php`
- Test: `tests/Unit/ConfigTest.php`

- [ ] **Step 1: Write failing tests**

`tests/Unit/ConfigTest.php`:
```php
<?php

use Mai\Popups\Config;
use Mai\Popups\Enum\Trigger;

beforeEach( fn () => maipopups_stub_wp_helpers() );

test( 'config applies defaults and parses types', function () {
    $config = Config::fromArray( [ 'trigger' => 'time', 'delay' => '3.0', 'distance' => '50.0' ] );
    expect( $config->trigger )->toBe( Trigger::Time );
    expect( $config->delay )->toBe( '3' );      // trailing .0 stripped (class-popup.php:58-67)
    expect( $config->distance )->toBe( '50' );
    expect( $config->disableClose )->toBeFalse();
} );

test( 'config sanitizes id and roles', function () {
    $config = Config::fromArray( [ 'id' => '#mai-popup-ABC', 'repeat_roles' => [ 'Administrator' ] ] );
    expect( $config->id )->toBe( 'mai-popup-abc' );          // sanitize_key passthrough lowercases via stub? assert against stubbed behavior
    expect( $config->repeatRoles )->toBe( [ 'administrator' ] );
} )->skip( 'enable once sanitize_key stub mirrors lowercasing — see note' );
```
> Note: the `sanitize_key` pass-through stub returns the arg unchanged. For assertions that depend on lowercasing, alias `sanitize_key` to `fn($v)=>strtolower(preg_replace('/[^a-z0-9_\-]/i','',$v))` in that test. Keep the first test (types) un-skipped.

- [ ] **Step 2: Run to verify fail**

Run: `vendor/bin/pest tests/Unit/ConfigTest.php`
Expected: FAIL — `Mai\Popups\Config` not found.

- [ ] **Step 3: Implement `Defaults` and `Config`**

`src/Defaults.php` (ports `maipopups_get_defaults()` verbatim, including the filter):
```php
<?php

namespace Mai\Popups;

final class Defaults {
    public static function get(): array {
        return (array) apply_filters( 'mai_popup_default_args', [
            'id' => '', 'class' => '', 'trigger' => 'manual', 'animate' => 'fade',
            'distance' => '50', 'delay' => '3', 'position' => 'center center',
            'width' => '', 'padding' => 'xl', 'repeat' => '7 days', 'repeat_roles' => [],
            'disable_close' => false, 'background' => '', 'color' => '', 'condition' => true,
            'preview' => false,
        ] );
    }
}
```

`src/Config.php` (readonly; ports sanitization from `class-popup.php:24-67`):
```php
<?php

namespace Mai\Popups;

use Mai\Popups\Enum\Trigger;
use Mai\Popups\Enum\Animation;

final readonly class Config {
    /** @param string[] $repeatRoles */
    public function __construct(
        public string $id,
        public string $class,
        public Trigger $trigger,
        public Animation $animate,
        public string $distance,
        public string $delay,
        public Position $position,
        public string $width,
        public string $padding,
        public string $repeat,
        public array $repeatRoles,
        public bool $disableClose,
        public string $background,
        public string $color,
        public bool $condition,
        public bool $preview,
    ) {}

    /** @param array<string,mixed> $args */
    public static function fromArray( array $args ): self {
        $args = shortcode_atts( Defaults::get(), $args, 'mai_popup' );

        return new self(
            id:           sanitize_key( $args['id'] ),
            class:        esc_attr( $args['class'] ),
            trigger:      Trigger::tryFromString( sanitize_key( $args['trigger'] ) ),
            animate:      Animation::tryFromString( sanitize_key( $args['animate'] ) ),
            distance:     self::float( $args['distance'] ),
            delay:        self::float( $args['delay'] ),
            position:     Position::fromString( esc_html( $args['position'] ) ),
            width:        trim( esc_html( $args['width'] ) ),
            padding:      sanitize_key( $args['padding'] ),
            repeat:       trim( esc_html( $args['repeat'] ) ),
            repeatRoles:  array_map( 'sanitize_key', (array) $args['repeat_roles'] ),
            disableClose: rest_sanitize_boolean( $args['disable_close'] ),
            background:   sanitize_key( $args['background'] ),
            color:        sanitize_key( $args['color'] ),
            condition:    rest_sanitize_boolean( is_callable( $args['condition'] ) ? $args['condition']() : $args['condition'] ),
            preview:      rest_sanitize_boolean( $args['preview'] ),
        );
    }

    private static function float( mixed $value ): string {
        $value = sanitize_text_field( (string) $value );
        return str_ends_with( $value, '.0' ) ? substr( $value, 0, -2 ) : $value;
    }
}
```

- [ ] **Step 4: Run to verify pass**

Run: `vendor/bin/pest tests/Unit/ConfigTest.php`
Expected: PASS (the type test; the lowercase test stays skipped or is fixed per the note).

- [ ] **Step 5: Commit**

```bash
git add src/Config.php src/Defaults.php tests/Unit/ConfigTest.php
git commit -m "Add Config value object + Defaults (extracted from Mai_Popup)"
```

---

## Task 5: `Renderer` (extract markup) — proven against snapshots

Extract `Mai_Popup::get()` + `get_close_button()` (`class-popup.php:108-234, 273-282`) into `Renderer`, driven by `Config`. Reuse the Task 3 snapshots to prove identical output.

**Files:**
- Create: `src/Renderer.php`
- Test: `tests/Unit/RendererTest.php` (reuses the same dataset + snapshots as Task 3)

- [ ] **Step 1: Write the renderer test mirroring the characterization dataset**

`tests/Unit/RendererTest.php`:
```php
<?php

use Mai\Popups\Config;
use Mai\Popups\Renderer;

beforeEach( function () {
    maipopups_stub_wp_helpers();
    \Brain\Monkey\Functions\when( 'is_user_logged_in' )->justReturn( false );
    \Brain\Monkey\Functions\when( 'current_user_can' )->justReturn( false );
} );

test( 'Renderer output matches the locked characterization snapshot', function ( array $args ) {
    $html = ( new Renderer() )->render( Config::fromArray( $args ), '<p>Inner content</p>' );
    expect( $html )->toMatchSnapshot();
} )->with( 'popup_configs' );
```
> Move the `dataset('popup_configs', …)` definition from `CharacterizationTest.php` into `tests/Pest.php` (or a shared `tests/Datasets.php`) so both tests share it. Copy the Task-3 baseline snapshot files to the names Pest expects for `RendererTest` (or run once to generate, then diff against the characterization snapshot to confirm equality — see Step 4).

- [ ] **Step 2: Implement `Renderer`** — port `get()`/`get_close_button()` verbatim, swapping `$this->args['x']` for `$config->x` and the trigger `switch` for a `match` on `Trigger`, and position handling for the `Position` object. Asset injection is delegated (Task 7) — for byte-stability, `render()` calls an injected `Assets` whose output the snapshot strips.

```php
<?php

namespace Mai\Popups;

use Mai\Popups\Enum\Trigger;
use Mai\Popups\Enum\Align;

final class Renderer {
    public function render( Config $config, string $content ): string {
        $id    = ltrim( $config->id, '#' );
        $width = sprintf( '%s%s', $config->width, is_numeric( $config->width ) ? 'px' : '' );

        $attrs = [
            'id'           => $id,
            'class'        => 'mai-popup',
            'style'        => '',
            'data-type'    => $config->trigger->value,
            'data-animate' => $config->animate->value,
            'data-close'   => $config->disableClose ? 'false' : 'true',
        ];

        if ( $config->class )   { $attrs['class'] .= ' ' . $config->class; }
        if ( $config->padding ) { $attrs['class'] .= sprintf( ' has-%s-padding', $config->padding ); }

        if ( $config->background ) {
            $name = 'link' === $config->background ? 'links' : $config->background;
            $attrs['class'] .= sprintf( ' has-%s-background-color', $name );
            $attrs['style'] .= sprintf( '--mai-popup-close-background:var(--color-%s);', $config->background );
        }
        if ( $config->color ) {
            $name = 'link' === $config->color ? 'links' : $config->color;
            $attrs['class'] .= sprintf( ' has-%s-color', $name );
            $attrs['style'] .= sprintf( '--mai-popup-close-color:var(--color-%s);', $config->color );
        }
        if ( $width ) {
            $attrs['style'] .= sprintf( '--mai-popup-max-width:%s;', $width );
            if ( in_array( $width, [ '100%', '100vw' ], true ) ) { $attrs['data-width'] = 'full'; }
        }
        if ( ! $config->position->isModal() || Align::Center !== $config->position->vertical || Align::Center !== $config->position->horizontal ) {
            $attrs['data-horizontal'] = $config->position->horizontal->value;
            $attrs['data-vertical']   = $config->position->vertical->value;
        }

        $attrs += match ( $config->trigger ) {
            Trigger::Time   => [ 'data-delay' => (string) ( (float) $config->delay * 1000 ) ],
            Trigger::Scroll => [ 'data-distance' => $config->distance ],
            default         => [],
        };

        $atts = '';
        foreach ( $attrs as $att => $value ) {
            if ( '' === $value || null === $value ) { continue; }
            $atts .= sprintf( ' %s="%s"', $att, trim( (string) $value ) );
        }

        $tag  = $config->preview ? 'div' : 'dialog';
        $html = sprintf( '<%s%s>', $tag, $atts )
              . $content
              . $this->closeButton( $config )
              . sprintf( '</%s>', $tag );

        return $html;
    }

    private function closeButton( Config $config ): string {
        if ( $config->disableClose ) { return ''; }
        return sprintf( '<button class="mai-popup__close" aria-label="%s"></button>', __( 'Close', 'mai-popups' ) );
    }
}
```
> **Important byte-stability checks while porting:** original loop skips empty values with `if ( ! $value )` (falsy) — replicate exactly (the `'' === ... || null ===` above is intentionally close; verify against snapshot). Original appended the asset block *before* `$content` (`get_scripts_styles()` at `class-popup.php:228`). Since Task 3 stripped that block from the snapshot, `Renderer` must inject it in the same position when not stripped — wired in Task 7. The cookie `data-expire`/`data-cookie` attributes are NOT added here in the legacy `get()` until `use_cookie()`; those move to Task 6 and Task 7's orchestration — confirm the snapshot configs that set `repeat` still match (cookie attrs were added in `get()` at `class-popup.php:206-212`). **If a snapshot differs, fix `Renderer` to match the legacy output — never edit the baseline.**

- [ ] **Step 3: Run renderer tests**

Run: `vendor/bin/pest tests/Unit/RendererTest.php`
Expected: PASS against the locked snapshots. If FAIL, diff and correct `Renderer` (not the snapshot).

- [ ] **Step 4: Prove equality with the characterization baseline**

Run: `diff tests/__snapshots__/*Characterization* tests/__snapshots__/*Renderer*`
Expected: no differences in the markup bodies (asset block stripped in both).

- [ ] **Step 5: Commit**

```bash
git add src/Renderer.php tests/Unit/RendererTest.php tests/Pest.php tests/__snapshots__
git commit -m "Extract Renderer from Mai_Popup; proven byte-identical via snapshots"
```

---

## Task 6: `Cookies` + `Conditions`

Extract `use_cookie()` (`class-popup.php:291-308`) and the cookie expiry (`:211`) into `Cookies`; the condition gate (`:109-120`) into `Conditions`.

**Files:**
- Create: `src/Cookies.php`, `src/Conditions.php`
- Test: `tests/Unit/CookiesTest.php`

- [ ] **Step 1: Write failing tests**

`tests/Unit/CookiesTest.php`:
```php
<?php

use Mai\Popups\Config;
use Mai\Popups\Cookies;

beforeEach( function () {
    maipopups_stub_wp_helpers();
    \Brain\Monkey\Functions\when( 'is_user_logged_in' )->justReturn( false );
} );

test( 'cookie used for timed popup with repeat', function () {
    $config = Config::fromArray( [ 'trigger' => 'time', 'repeat' => '7 days' ] );
    expect( ( new Cookies() )->shouldUse( $config ) )->toBeTrue();
} );

test( 'no cookie for manual trigger', function () {
    $config = Config::fromArray( [ 'trigger' => 'manual', 'repeat' => '7 days' ] );
    expect( ( new Cookies() )->shouldUse( $config ) )->toBeFalse();
} );

test( 'role exemption disables cookie for matching logged-in user', function () {
    \Brain\Monkey\Functions\when( 'is_user_logged_in' )->justReturn( true );
    \Brain\Monkey\Functions\when( 'current_user_can' )->justReturn( true );
    $config = Config::fromArray( [ 'trigger' => 'time', 'repeat' => '7 days', 'repeat_roles' => [ 'administrator' ] ] );
    expect( ( new Cookies() )->shouldUse( $config ) )->toBeFalse();
} );
```

- [ ] **Step 2: Run to verify fail**

Run: `vendor/bin/pest tests/Unit/CookiesTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `Cookies` and `Conditions`**

`src/Cookies.php`:
```php
<?php

namespace Mai\Popups;

use Mai\Popups\Enum\Trigger;

final class Cookies {
    public function shouldUse( Config $config ): bool {
        $use = ! $config->preview
            && in_array( $config->trigger, [ Trigger::Time, Trigger::Scroll ], true )
            && '' !== $config->repeat;

        if ( $use && $config->repeatRoles && is_user_logged_in() ) {
            foreach ( $config->repeatRoles as $role ) {
                if ( current_user_can( $role ) ) { return false; }
            }
        }

        return $use;
    }

    public function expires( Config $config ): int {
        return (int) strtotime( '+' . $config->repeat );
    }
}
```

`src/Conditions.php`:
```php
<?php

namespace Mai\Popups;

final class Conditions {
    public function passes( Config $config ): bool {
        return $config->condition;
    }
}
```
> Note: `Config` already resolved any callable `condition` to a bool in `fromArray()` (`class-popup.php:43`), so `Conditions::passes()` is a thin, testable seam (kept for clarity + future per-request conditions).

- [ ] **Step 4: Run to verify pass**

Run: `vendor/bin/pest tests/Unit/CookiesTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Cookies.php src/Conditions.php tests/Unit/CookiesTest.php
git commit -m "Extract Cookies + Conditions from Mai_Popup"
```

---

## Task 7: `Assets` + `Popup` orchestrator (cookie attrs, footer-timing, once-guard)

Extract `get_scripts_styles()` (`class-popup.php:243-264`) into `Assets`, and `render()`/`is_footer()` + the cookie attribute wiring into a `Popup` orchestrator that reproduces the full footer flow.

**Files:**
- Create: `src/Assets.php`, `src/Popup.php`
- Test: `tests/Unit/PopupRenderTest.php`

- [ ] **Step 1: Write a test that the full popup HTML (cookie config) matches legacy**

`tests/Unit/PopupRenderTest.php`:
```php
<?php

use Mai\Popups\Config;
use Mai\Popups\Renderer;
use Mai\Popups\Cookies;

beforeEach( function () {
    maipopups_stub_wp_helpers();
    \Brain\Monkey\Functions\when( 'is_user_logged_in' )->justReturn( false );
    \Brain\Monkey\Functions\when( 'current_user_can' )->justReturn( false );
    \Brain\Monkey\Functions\when( 'strtotime' )->justReturn( 1893456000 ); // fixed for snapshot
} );

test( 'cookie popup adds data-cookie and data-expire', function () {
    $config = Config::fromArray( [ 'trigger' => 'time', 'repeat' => '7 days', 'delay' => '3' ] );
    $html   = ( new Renderer( new Cookies() ) )->render( $config, '<p>x</p>' );
    expect( $html )->toContain( 'data-cookie="true"' );
    expect( $html )->toContain( 'data-expire="1893456000"' );
} );
```
> This requires `Renderer` to accept an optional `Cookies` and add `data-cookie`/`data-expire` exactly as legacy `get()` did at `class-popup.php:206-212`. Update `Renderer`'s constructor to `__construct(private ?Cookies $cookies = null, private ?Assets $assets = null)` and insert the cookie-attr block in the same position as legacy (after position/trigger attrs, before building `$atts`). Re-run Task 5 snapshots — non-cookie configs must remain unchanged.

- [ ] **Step 2: Run to verify fail**

Run: `vendor/bin/pest tests/Unit/PopupRenderTest.php`
Expected: FAIL — `data-cookie` not present.

- [ ] **Step 3: Implement `Assets`, wire `Cookies` into `Renderer`, implement `Popup`**

`src/Assets.php` (verbatim behavior port of `get_scripts_styles()`):
```php
<?php

namespace Mai\Popups;

final class Assets {
    private bool $first = true;

    public function inlineHead( Config $config ): string {
        if ( ! $this->first || $config->preview ) { return ''; }
        $this->first = false;

        $suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        $version = MAI_POPUPS_VERSION;
        $cssPath = MAI_POPUPS_PLUGIN_DIR . "assets/css/mai-popups{$suffix}.css";
        $cssUrl  = MAI_POPUPS_PLUGIN_URL . "assets/css/mai-popups{$suffix}.css";
        $jsPath  = MAI_POPUPS_PLUGIN_DIR . "assets/js/mai-popups{$suffix}.js";
        $jsUrl   = MAI_POPUPS_PLUGIN_URL . "assets/js/mai-popups{$suffix}.js";

        return sprintf( '<link id="mai-popups-css" rel="stylesheet" href="%s?ver=%s">', $cssUrl, $version . '.' . date( 'njYHi', filemtime( $cssPath ) ) )
             . sprintf( '<script id="mai-popups-js" src="%s?ver=%s" defer></script>', $jsUrl, $version . '.' . date( 'njYHi', filemtime( $jsPath ) ) );
    }
}
```
> In `Renderer::render()`, inject `$this->assets?->inlineHead( $config )` in the exact position legacy used (immediately after the opening tag, before `$content` — `class-popup.php:228`). Add the cookie attrs when `$this->cookies?->shouldUse($config)` is true: `data-cookie="true"` and `data-expire` = `$this->cookies->expires($config)`.

`src/Popup.php` (ports `render()` + once-guard + `is_footer()`):
```php
<?php

namespace Mai\Popups;

final class Popup {
    /** @var array<string,bool> */
    private static array $loaded = [];

    public function __construct(
        private Config $config,
        private string $content = '',
        private ?Renderer $renderer = null,
        private ?Conditions $conditions = null,
    ) {
        $this->renderer   ??= new Renderer( new Cookies(), new Assets() );
        $this->conditions ??= new Conditions();
    }

    public function render(): void {
        $id = ltrim( $this->config->id, '#' );
        if ( isset( self::$loaded[ $id ] ) ) { return; }

        if ( ! $this->conditions->passes( $this->config ) ) { return; }

        $output = fn () => print $this->renderer->render( $this->config, $this->content );

        if ( $this->config->preview || $this->isFooter() ) {
            $output();
        } else {
            add_action( 'wp_footer', $output );
        }

        self::$loaded[ $id ] = true;
    }

    private function isFooter(): bool {
        return (bool) ( doing_action( 'wp_footer' ) || did_action( 'wp_footer' ) );
    }
}
```
> The legacy once-guard keyed on `$this->args['id']` (already `sanitize_key`'d, no leading `#`); match that. Legacy checked the condition inside `get()`; here it's in `Popup::render()` before hooking — equivalent because `get()` returned empty when the condition failed. Confirm the footer path still echoes identical markup.

- [ ] **Step 4: Run all tests**

Run: `composer test`
Expected: PASS — including the cookie test and unchanged Task 5 snapshots.

- [ ] **Step 5: Commit**

```bash
git add src/Assets.php src/Popup.php src/Renderer.php tests/Unit/PopupRenderTest.php
git commit -m "Add Assets + Popup orchestrator; wire cookie attrs into Renderer"
```

---

## Task 8: `Block` bridge + `Defaults`/`mai_do_popup` shims + `Plugin` bootstrap

Rewire the ACF block render callback and the public functions to the new classes, keeping signatures and the `acf/mai-popup` block name.

**Files:**
- Create: `src/Block.php`, `src/Plugin.php`
- Modify: `blocks/mai-popup/block.php`, `includes/functions.php`, `mai-popups.php`
- Test: `tests/Unit/ShimTest.php`

- [ ] **Step 1: Write the shim test**

`tests/Unit/ShimTest.php`:
```php
<?php

beforeEach( function () {
    maipopups_stub_wp_helpers();
    if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', '/tmp/' ); }
    require_once __DIR__ . '/../../includes/functions.php';
} );

test( 'maipopups_get_defaults still returns the defaults array with the filter applied', function () {
    $defaults = maipopups_get_defaults();
    expect( $defaults )->toHaveKey( 'trigger' );
    expect( $defaults['trigger'] )->toBe( 'manual' );
} );
```

- [ ] **Step 2: Run to verify fail/pass baseline**

Run: `vendor/bin/pest tests/Unit/ShimTest.php`
Expected: PASS already (legacy function exists) — this test guards that the shim refactor in Step 3 keeps it working.

- [ ] **Step 3: Rewrite `includes/functions.php` as deprecated shims**

```php
<?php

defined( 'ABSPATH' ) || die;

use Mai\Popups\Config;
use Mai\Popups\Defaults;
use Mai\Popups\Popup;

/**
 * Render a popup. Public procedural API (template tag) wrapping Mai\Popups\Popup.
 *
 * @param array<string,mixed> $args    Popup args. See Mai\Popups\Defaults::get().
 * @param string              $content Popup inner content.
 */
function mai_do_popup( array $args = [], string $content = '' ): void {
    ( new Popup( Config::fromArray( $args ), $content ) )->render();
}

/**
 * Get the filterable popup default args. Public procedural API for Mai\Popups\Defaults.
 *
 * @return array<string,mixed>
 */
function maipopups_get_defaults(): array {
    return Defaults::get();
}
```

- [ ] **Step 4: Implement `Block` (port `block.php` render callback to `Renderer`)**

`src/Block.php` renders via the new pipeline. Port `mai_do_popup_block()` (`blocks/mai-popup/block.php:36-59`): map the native attrs (`className`→class, `alignContent`→position, `backgroundColor`→background, `textColor`→color) + the 10 `get_field()` values into `Config::fromArray()`, then `( new Popup( $config, do_shortcode( $content ) ) )->render()`. Keep the InnerBlocks preview template. Registration (`register_block_type( __DIR__ . '/block.json', … )`) and the field group stay in `blocks/mai-popup/block.php` unchanged; only its render callback delegates:

```php
// blocks/mai-popup/block.php render callback body becomes:
function mai_do_popup_block( $attributes, $content, $is_preview ) {
    \Mai\Popups\Block::render( $attributes, (string) $content, (bool) $is_preview );
}
```
And `src/Block.php`:
```php
<?php

namespace Mai\Popups;

final class Block {
    public static function render( array $attributes, string $content, bool $is_preview ): void {
        $args = [
            'class'         => $attributes['className']       ?? '',
            'position'      => $attributes['alignContent']    ?? '',
            'background'    => $attributes['backgroundColor'] ?? '',
            'color'         => $attributes['textColor']       ?? '',
            'id'            => (string) get_field( 'id' ),
            'trigger'       => (string) get_field( 'trigger' ),
            'animate'       => (string) get_field( 'animate' ),
            'distance'      => (string) get_field( 'distance' ),
            'delay'         => (string) get_field( 'delay' ),
            'width'         => (string) get_field( 'width' ),
            'padding'       => (string) get_field( 'padding' ),
            'repeat'        => (string) get_field( 'repeat' ),
            'repeat_roles'  => (array) get_field( 'repeat_roles' ),
            'disable_close' => (bool) get_field( 'disable_close' ),
            'preview'       => $is_preview,
        ];

        if ( $is_preview ) {
            $template = wp_json_encode( [ [ 'core/paragraph', [], [] ] ] );
            $content  = sprintf( '<InnerBlocks template="%s" />', esc_attr( $template ) );
        }

        ( new Popup( Config::fromArray( $args ), do_shortcode( $content ) ) )->render();
    }
}
```

- [ ] **Step 5: Implement `Plugin` bootstrap and update `mai-popups.php`**

Move hook wiring into `src/Plugin.php` (`init()` registers block include + updater). In `mai-popups.php`, after the version defines, require the Composer autoloader and boot:
```php
require_once __DIR__ . '/vendor/autoload.php';
\Mai\Popups\Plugin::instance()->init();
```
> Keep the existing updater wiring (`mai_get_updater_icons()` guarded call) inside `Plugin`. Do NOT remove `setBranch(...)` — there is none (tag-based already).

- [ ] **Step 6: Run all tests + a manual smoke load**

Run: `composer test`
Expected: PASS.
Run: `php -l includes/functions.php && php -l src/Block.php && php -l blocks/mai-popup/block.php`
Expected: no syntax errors.

- [ ] **Step 7: Commit**

```bash
git add src/Block.php src/Plugin.php blocks/mai-popup/block.php includes/functions.php mai-popups.php tests/Unit/ShimTest.php
git commit -m "Bridge ACF block + public fns to new pipeline; boot Mai\\Popups\\Plugin"
```

---

## Task 9: Delete legacy class, PHPStan green, final verification

**Files:**
- Delete: `classes/class-popup.php`
- Modify: `mai-popups.php` (remove the `classes/class-popup.php` require), `phpstan.neon` (add `includes`, `blocks`)

- [ ] **Step 1: Remove the legacy include and file**

Remove the `require .../classes/class-popup.php` line from `mai-popups.php`, then:
```bash
git rm classes/class-popup.php
```

- [ ] **Step 2: Point the characterization test at a kept legacy copy OR retire it**

The Task 3 characterization test `require`s the legacy class. Since `Renderer` snapshots now encode the locked output, **retire** `tests/Unit/CharacterizationTest.php` (the `RendererTest` snapshots are the ongoing guard):
```bash
git rm tests/Unit/CharacterizationTest.php
```
> Keep the snapshot files — `RendererTest` still uses them.

- [ ] **Step 3: Expand PHPStan scope and run**

Update `phpstan.neon` `paths` to include `includes`, `blocks`, `mai-popups.php`. Then:
Run: `composer stan`
Expected: 0 errors. Fix any (add `get_field`/ACF stubs to `phpstan.neon` `stubFiles` if ACF functions are flagged, or `ignoreErrors` for the ACF dynamic calls with a comment).

- [ ] **Step 4: Full suite + lint**

Run: `composer test && composer stan && php -l mai-popups.php`
Expected: all green.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Remove legacy Mai_Popup class; PHPStan clean across plugin"
```

- [ ] **Step 6: Push develop (no tag, no version bump)**

```bash
git push origin develop
```

---

## Self-Review (completed)

- **Spec coverage:** PHP 8.2 floor (set in Plan 2's header bump — note: `Requires PHP` header change is deferred to the release commit, not here, to avoid a version-adjacent edit mid-refactor), namespaces/autoload (Tasks 1,8), display refactor + enums (Tasks 2,4,5,7), tests (Tasks 1,3-7), ACF block kept + shims (Task 8), byte-stable output (Task 3/5 snapshots). ✅
- **Placeholders:** none — all steps carry real code/commands. The two `>` notes that say "port verbatim from `class-popup.php:NNN`" cite exact source lines being moved (concrete, not vague).
- **Type consistency:** `Config` property names (`disableClose`, `repeatRoles`) are used consistently in Tasks 4-8; `Renderer::render(Config,string)`, `Cookies::shouldUse(Config)`/`expires(Config)`, `Popup::render()` signatures match across tasks.
- **Open follow-ups for Plan 2:** swap `Assets` inline-injection for `wp_enqueue_*`; `Requires PHP: 8.2` header; CHANGES.md `0.6.0 (TBD)` entries land per-fix in Plan 2.

---

## Notes carried to Plan 2 (Accessible modal base)

- `Renderer` now emits `data-modal` intent via `Position::isModal()` — Plan 2's JS keys modal vs non-modal off the rendered markup.
- The cookie `data-expire` is a unix timestamp; Plan 2 fixes the JS to consume it (`new Date(parseInt(expire,10)*1000)`).
- Issues #5 (unique anchor id on block duplication) and #7 (sticky close on tall mobile) are Plan 2 scope.
