# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Joomla **administrator** module (`mod_contentcalendar`, `client="administrator"`) that renders a monthly calendar of `com_content` articles in the admin backend, grouped by `publish_up` date. Targets Joomla 5 and 6. This is the **FREE** edition of a commercial product (Joomill Content Calendar PRO); most configurable behavior is intentionally stubbed and gated behind PRO.

The repo root holds a packaging artifact (`mod_contentcalendar_v1.0.0.zip`); the actual source lives in `mod_contentcalendar/`.

## Build / test / install

There is no build system, package manager, linter config, or test suite. It is a hand-packaged Joomla module.

- **Install / package:** zip the *contents* of `mod_contentcalendar/` (so `mod_contentcalendar.xml` sits at the zip root) and install via Joomla's Extension Manager. The XML uses `method="upgrade"`, so reinstalling over an existing copy updates it.
- **Local dev:** Laragon at `C:\laragon\www\dev\extensions\`. Edit files in place inside a Joomla install's `administrator/modules/mod_contentcalendar/` to test live, or reinstall the zip.
- **Code style:** Joomla CMS standard (tab indentation, `defined('_JEXEC') or die;` guard on every PHP file, `@since` docblocks). Match the surrounding style; do not reformat to PSR-12 spaces.
- **Syntax check:** there are no tests, but you can lint PHP locally, e.g. `& "C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" -l <file>`. The Joomla 6 source for reference is at `C:\laragon\www\joomla6`.

## Architecture

### Entry point and request flow

The module renders through the standard Joomla dispatcher; there is **no** legacy `mod_contentcalendar.php` entry file (Joomla 6 does not fall back to one when a Dispatcher class exists).

`services/provider.php` registers the `ModuleDispatcherFactory`, `HelperFactory`, and core `Module` service provider. Joomla's dispatcher factory instantiates `src/Dispatcher/Dispatcher.php` with the standard `($module, $app, $input)` signature, so the Dispatcher does **not** override the constructor. `Dispatcher::getLayoutData()` reads `params`/`app` from `parent::getLayoutData()`, resolves the database from the DI container (`DatabaseInterface::class`), instantiates the two services, gates article loading on `core.manage` for `com_content`, and hands the calendar data to the template.

> Historical note: an earlier version overrode the constructor with `(DataAccessService, BusinessLogicService)` and shipped a duplicate `mod_contentcalendar.php`. That constructor signature does not match what the factory passes, so it fataled on Joomla 6. Do not reintroduce constructor injection into a module dispatcher — the factory does not support it; resolve dependencies inside `getLayoutData()`.

### Service split

Business logic is deliberately separated from data access; both are plain classes instantiated by the Dispatcher:

- **`src/Service/DataAccessService.php`** — all DB work via `DatabaseInterface`, using `quoteName` + bound parameters and a portable `publish_up` range filter (no MySQL-only `YEAR()`/`MONTH()`/`GROUP_CONCAT`). Tags are fetched in a separate query and merged in PHP (`attachTags`). Catches exceptions, logs via `Log::add`, returns `[]`/`false`.
- **`src/Service/BusinessLogicService.php`** — pure date/calendar math: `validateMonthYear`, `calculateNavigation`, `organizeArticlesByDay`, `prepareCalendarData`, plus week-grid helpers. No DB or request access.
- **`src/Helper/ContentCalendarHelper.php`** — static helper bridge. In FREE it is reduced to `getItemColorSimple()` returning a single hardcoded color (`#1a73e8`).

### Namespace

The XML declares the base namespace `Joomill\Module\Contentcalendar` (path `src`); classes live under `Joomill\Module\Contentcalendar\Administrator\...` (the `Administrator` segment is standard for admin-client modules). The DI registrations and `addfieldprefix` in the XML reflect this. `services/provider.php` declares **no** namespace (matching Joomla core convention).

### Template

`tmpl/default.php` receives `$moduleclass_sfx`, `$calendar_data`, and `$params`. It renders a month grid (Monday-first), localizes month/day names via `Text::_()` with PHP `date()` fallbacks, and renders each article as a link to `com_content` article edit with a base64 `return` param back to the dashboard. Assets are registered through the `WebAssetManager` (`media/css/default.css`, `media/js/default.js`).

## FREE vs PRO — what is intentionally inert

When editing, do not "fix" these by wiring them up; they are the upsell boundary:

- **`src/Field/ProField.php`** (`type="pro"`) renders a "PRO only" label instead of a real control. Most fields in `mod_contentcalendar.xml` (layout, weeks, colors, add-article, default publish time/category) use it.
- **`src/Field/UpgradeField.php`** (`type="upgrade"`) renders an upgrade call-to-action box.
- **`media/js/default.js`** is a stub: `init()`/`setupBasicCalendar()` do nothing functional, and helpers like `getCSRFToken`/`showMessage` exist but are unused. Drag-and-drop rescheduling is a PRO feature.
- **`DataAccessService`** carries PRO-only methods unused by the FREE flow: `getArticlesForDateRange`, `updateArticleDate`, `canEditArticle`. The FREE dispatcher only calls `getArticlesForMonth`.
- The calendar is always monthly view with a single color; week-range methods in `BusinessLogicService` (`buildWeeks`, `getMondayOfWeek`, etc.) support PRO layouts.

## Install script

`script.php` (`Mod_ContentcalendarInstallerScript::install`) auto-publishes the module to the admin `icon` position with access level 3 on first install. It does this with raw `#__modules` / `#__modules_menu` queries — edit carefully.

## Conventions / gotchas

- `@since` tags are inconsistent (mix of `1.0.0` and `2.0.0`); the real release version is the `<version>` in `mod_contentcalendar.xml` (currently `1.0.0`). Bump that for releases.
- DB query state filter is hardcoded to published + archived (`whereIn('a.state', [1, 2])`) and includes future-dated articles by design.
- Languages: full set under `language/<tag>/` (de, en-GB, es, fr, it, nl) — add new strings to **all** of them, especially `en-GB`.
