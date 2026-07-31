# Changelog

All notable changes to the Extension are documented in this file.

## 1.1.2 - Unreleased
- Fix: articles are now placed on the right calendar day on sites in a timezone other than UTC. Joomla's Date::format() forces UTC unless it is explicitly asked for local time, so the conversion of publish_up to the display timezone was silently a no-op, and the month and week queries compared their date bounds against the UTC column without converting them. Both are fixed together: an article scheduled just after midnight no longer shows up on the previous day, and the "today" highlight follows the user's timezone as well.
- Improvement: PHP file headers updated to the standard Joomla docblock copyright format; code style only, no functional changes
- Improvement: full code style pass against the Joomla CMS phpcs ruleset (PSR-12): phpcbf auto-fixes for indentation, line endings, brace placement and whitespace, plus phpcs annotations for deliberate exceptions (`_JEXEC` guards, legacy global class names, legacy API naming). Code style only, no functional changes
- Update: the requested calendar year is now limited to a window of 3 years around the current year (was 1970-2100), so the month navigation cannot be walked through decades of empty months

## 1.1.1 - 08/07/2026
- Addition: Downloads from the Joomill update server now include diagnostic request headers with site and environment information

## 1.1.0 - 02/07/2026
- Addition: Custom CSS field in the advanced options to add inline styling, output through the WebAssetManager
- Update: rebuilt the module install script (script.php) to the Joomla 4.2+ InstallerScriptInterface with a return statement, typed install/update/uninstall/preflight/postflight methods and minimum PHP/Joomla version checks.
- Update: moved the administrator dashboard auto-publish from install() to postflight('install') and wrapped the installer logic in try/catch logging.
- Addition: install and uninstall now show a localized thank-you/quickstart screen with Joomill social links; added the matching language strings (THANKYOU/QUICKSTART/CONFIGURATION/NEEDHELP) to all six languages.
- Update: brought the module manifest into line with the Joomill standard (element order, GPLv3 license without trailing semicolon, "(C)" copyright, section comments).
- Addition: the module edit screen's Help button now links to the Joomill documentation page (via a MOD_CONTENTCALENDAR_HELP_URL language string).
- Update: restyled the FREE upgrade notice on the config screen to a single inline success alert.
- Update: modernized the "pro" upsell form field to a namespaced ProField that renders a PRO badge linking to the upgrade page (replaces the legacy elements/pro.php).
