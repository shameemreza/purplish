# Contributing to Purplish

Thanks for wanting to help. Bug reports, fixes and ideas are all welcome. This guide covers the local setup, the checks every change has to pass, and how a change gets in.

Security issues don't go here. Please follow the [security policy](SECURITY.md) and report them privately.

## Local setup

You need:

- PHP 7.4 or later, with GD or Imagick.
- Composer.
- Node 22, the version in `.nvmrc`.
- MySQL or MariaDB for the PHPUnit suite.
- A WordPress site with WooCommerce, and one block theme and one classic theme to test with.

Clone this repo into the site's `wp-content/plugins/purplish` folder, then install the packages and build the scripts:

```
composer install
npm ci
npm run build
```

`npm run start` rebuilds the scripts while you work.

## The test database

The PHPUnit suite installs WordPress into its own database on every run and empties it. Never point it at a database you care about.

The settings come from these environment variables, with these defaults:

| Variable | Default |
|---|---|
| `WP_CORE_DIR` | The WordPress install four folders up |
| `WP_TESTS_DB_NAME` | `purplish_tests` |
| `WP_TESTS_DB_USER` | `root` |
| `WP_TESTS_DB_PASSWORD` | Empty |
| `WP_TESTS_DB_HOST` | `127.0.0.1` |

Outside a WordPress install, `bin/install-wp-tests.sh` downloads WordPress and creates the database. It reads the password from `WP_TESTS_DB_PASSWORD`, never from an argument:

```
WP_CORE_DIR=/tmp/wordpress bin/install-wp-tests.sh purplish_tests root 127.0.0.1 7.1
WP_CORE_DIR=/tmp/wordpress composer test
```

The tests need WooCommerce in the plugins folder of that WordPress copy.

## Checks

Every change has to pass these, and CI runs the same ones:

| Command | What it checks |
|---|---|
| `composer phpcs` | WordPress and WooCommerce coding standards |
| `composer lint:compat` | PHP 7.4 compatibility |
| `composer test` | PHPUnit |
| `npm run lint:js` | JavaScript standards |
| `npm run lint:css` | CSS standards |
| `npm run test:js` | Jest |
| `npm run build` | The scripts in `build/` |
| `npm run check:size` | Each block's front script is under 5 KB gzipped (run after the build) |

Anything that moves or loads on the front end also follows the speed list in the pull request template: CSS before script, no libraries, a still version for reduced motion, and a PageSpeed mobile run before and after.

Commit the `build/` folder with your change. WordPress.org installs the plugin from the repo files, with no build step.

## Accessibility and touch

Anything a shopper can click, tap, drag, hover or read on the front end has to work three ways before it ships: with a keyboard alone, with a screen reader, and on a phone. Search engines and AI agents read the same alt text and names a screen reader does, so this check covers them too.

Keyboard:

- Every control is a real `<button>`, link or form field, or has the role and keys of one. Tab reaches it, Enter or Space uses it, and Escape closes what it opened.
- Focus is always visible, and anything that shows on hover also shows on focus (`:focus-visible` or `:focus-within`).
- Closing a popup puts focus back where it was, and an open list closes when Tab moves past it.
- Anything that hides on a timer waits while focus or the pointer is inside it.

Screen reader:

- An image that shows something has alt text that matches what's on screen, and the alt text changes when the photo does. A decorative image gets `alt=""`.
- Every control has a name a screen reader can say. An icon button gets a label.
- A change without a page load is announced, through `aria-pressed`, `aria-expanded`, `aria-current` or a live region.
- Text that only shows on hover is still in the markup.

Touch:

- Every tap area is at least 24px square, even when the thing you see is smaller.
- Hover styles sit inside `@media (hover: hover)`, so nothing stays highlighted after a tap.
- A drag leaves vertical swipes to the page (`touch-action: pan-y`), and anything you can drag also works with a tap or a key.
- A button hidden until hover can't be tapped while it's hidden.

To test, go through the feature with the mouse put away, then with VoiceOver (Command F5 on a Mac) or NVDA, then on a phone or in the iOS Simulator. Chrome's Lighthouse accessibility audit catches missing names and alt text, but it can't tell you whether the feature makes sense, so it doesn't replace the three passes.

## How a change gets in

1. Open an issue, or comment on an existing one, so the change has a record.
2. Branch from main: `fix/<issue>-<slug>`, `feat/<issue>-<slug>` or `docs/<issue>-<slug>`.
3. Keep the change to what the issue needs. If you notice something else, open another issue.
4. Open a pull request that says `Closes #<issue>`, with how you tested it. Anything someone can see needs before and after screenshots, on a block theme and a classic theme.
5. Pull requests merge with a squash once the `CI passed` check is green.
