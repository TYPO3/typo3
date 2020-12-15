.. include:: ../../Includes.txt

.. _changelog-Breaking-93093-ReworkShortcutPHPAPI:

==========================================
Breaking: #93093 - Rework Shortcut PHP API
==========================================

See :issue:`93093`

Description
===========

The Shortcut PHP API previously has the full URL of the shortcut target
stored in the :sql:`sys_be_shortcuts` table. It however turned out that
this is not working well, error-prone and laborious. For example, all
created shortcuts are automatically invalid as soon as the corresponding
module changed its route path. Furthermore the :sql:`url` column included
the token which was actually never used but regenerated on every link
generation, e.g. when reloading the backend. Since even the initial
`returnUrl` was stored in the database, a shortcut which linked to
FormEngine has returned to this initial url forever. Even after years
when the initial target may no longer available.

All these characteristics opposes the introduction of speaking urls for
the TYPO3 backend. Therefore, the internal handling and registration of
the Shortcut PHP API was reworked.

A shortcut record does now not longer store the full url of the shortcut
target but instead only the modules route identifier and the necessary
arguments (parameters) for the URL.

The columns :sql:`module_name` and :sql:`url` of the :php:`sys_be_shortcuts`
table have been replaced with:

* :sql:`route` - Contains the route identifier of the module to link to
* :sql:`arguments` - Contains all necessary arguments (parameters) for the
link as JSON encoded string

The :sql:`arguments` column does therefore not longer store any of the
following parameters:

* `route`
* `token`
* `returnUrl`

Shortcuts are usually created through the JavaScript function
:js:`TYPO3.ShortcutMenu.createShortcut()` which performs an AJAX call to
:php:`ShortcutController->addAction()`. The parameter signature of the
JavaScript function has therefore been changed and the :php:`addAction()`
method does now feature an additional result string `missingRoute`, in case
no `routeIdentifier` was provided in the AJAX call.

The parameter signature changed as followed:

.. code-block:: javascript

   // Old signature:
	public createShortcut(
      moduleName: string,
      url: string,
      confirmationText: string,
      motherModule: string,
      shortcutButton: JQuery,
      displayName: string,
	)

	// New signature:
	public createShortcut(
      routeIdentifier: string,
      routeArguments: string,
      displayName: string,
      confirmationText: string,
      shortcutButton: JQuery,
	)

The :php:`TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton`
API for generating such creation links now provides a new public method
:php:`setRouteIdentifier()` which replaces the deprecated
:php:`setModuleName()` method. See
:ref:`changelog-Deprecation-93093-DeprecateMethodNameInShortcutPHPAPI` for
all deprecations done during the rework.


Impact
======

Directly calling :js:`TYPO3.ShortcutMenu.createShortcut()` with the old
parameter signature will result in a JavaScript error.

Already created shortcuts won't be available prior to running the provided
upgrade wizard.

The columns :sql:`module_name` and :sql:`url` have been removed. Directly
querying these columns will raise a doctrine dbal exception.


Affected Installations
======================

Installations with already created shortcuts.

Installations with custom extensions directly calling
:js:`TYPO3.ShortcutMenu.createShortcut()` with the old parameter signatur.

Installations with custom extensions, directly using the columns
:sql:`module_name` and :sql:`url` or relying on them being filled.

Installations with custom extensions using deprecated functionality of
the Shortcut PHP API.


Migration
=========

Update the database schema (only "Add fields to tables") and run the
`shortcutRecordsMigration` upgrade wizard either in the install tool or on
CLI with
:shell:`./typo3/sysext/core/bin/typo3 upgrade:run shortcutRecordsMigration`.
Remove the unused :sql:`module_name` and :sql:`url` columns only after running
the wizard.

Change any call to :js:`TYPO3.ShortcutMenu.createShortcut()` to use the new
parameter signature.

Migrate custom extension code to use :sql:`route` and :sql:`arguments` instead
of :sql:`module_name` and :sql:`url`.

Migrate any call to deprecated functionality of the Shortcut PHP API.

.. index:: Backend, PHP-API, PartiallyScanned, ext:backend
