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

Commit the `build/` folder with your change. WordPress.org installs the plugin from the repo files, with no build step.

## How a change gets in

1. Open an issue, or comment on an existing one, so the change has a record.
2. Branch from main: `fix/<issue>-<slug>`, `feat/<issue>-<slug>` or `docs/<issue>-<slug>`.
3. Keep the change to what the issue needs. If you notice something else, open another issue.
4. Open a pull request that says `Closes #<issue>`, with how you tested it. Anything someone can see needs before and after screenshots, on a block theme and a classic theme.
5. Pull requests merge with a squash once the `CI passed` check is green.
