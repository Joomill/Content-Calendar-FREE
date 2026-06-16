# Changelog

All notable changes to the Extension are documented in this file.

## 1.1.0 [UNRELEASED]
- Update: rebuilt the module install script (script.php) to the Joomla 4.2+ InstallerScriptInterface with a return statement, typed install/update/uninstall/preflight/postflight methods and minimum PHP/Joomla version checks.
- Update: moved the administrator dashboard auto-publish from install() to postflight('install') and wrapped the installer logic in try/catch logging.
- Addition: install and uninstall now show a localized thank-you/quickstart screen with Joomill social links; added the matching language strings (THANKYOU/QUICKSTART/CONFIGURATION/NEEDHELP) to all six languages.

## TODO
- Update: restyled the FREE upgrade notice on the config screen to a single inline success alert.
- Update: modernized the "pro" upsell form field to a namespaced ProField that renders a PRO badge linking to the upgrade page (replaces the legacy elements/pro.php).
- Addition: help buttons now link to the Joomill documentation page
- Check other updates in the past: https://github.com/joomla/Manual/tree/main/updates
- Check bc for Joomla 7 release: https://github.com/joomla/Manual/blob/main/updates/64-70/removed-backward-incompatibility.md
