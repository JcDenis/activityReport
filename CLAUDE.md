# activityReport

Notes for AI assistants working on the Dotclear plugin activityReport.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

Log and receive your blog activity by email, feed, or on dashboard A user guide with screenshots is available in French: [guide d'utilisation](https://github.com/JcDenis/activityReport/blob/master/docs/fr/utilisation.md).

Admin page and configuration page. Public side: behaviors.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/Action.php`: action descriptor class.
* `src/ActivityReport.php`: main class.
* `src/ActivityReportAction.php`: register default activities and export mail formats.
* `src/ActivityRow.php`: activity record row descriptor class.
* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/BackendBehaviors.php`: admin behaviors.
* `src/Combo.php`: combo class.
* `src/Config.php`: configuration page of the module (plugins manager).
* `src/Format.php`: format decriptor.
* `src/Formats.php`: formats stack.
* `src/Frontend.php`: public side: behaviors, template tags and URL handlers registration.
* `src/FrontendContext.php`: frontend context class.
* `src/FrontendTemplate.php`: template tags.
* `src/FrontendUrl.php`: public URL handler.
* `src/Group.php`: actions stack class.
* `src/Groups.php`: actions groups stack class.
* `src/Install.php`: install and update, database structure (a change needs a migration here).
* `src/Manage.php`: admin page of the module.
* `src/ManageList.php`: admin page (list).
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/Prepend.php`: loaded on every request: autoload, URL handlers, permission types.
* `src/Settings.php`: module settings.
* `src/Uninstall.php`: cleaners for the plugin Uninstaller.
* `js/`: `backend.js`.
* `default-templates/`: public templates.
* Database table `activity`.
* Behaviors used: `adminDashboardFavoritesV2`, `adminDashboardContentsV2`, `adminDashboardOptionsFormV2`, `adminAfterDashboardOptionsUpdate`, `adminFiltersListsV2`, `adminColumnsListsV2`.

## Pitfalls

* Table structure changes need a migration in `src/Install.php` (version compare), and must work on MySQL, PostgreSQL and SQLite.

## Testing

* Check the admin page, the configuration page, the admin help, in English and French.
* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
* The user guide `docs/fr/utilisation.md` describes the features: update it, and its screenshots, when the interface changes.
