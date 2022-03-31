.. include:: /Includes.rst.txt

========================================================
Deprecation: #94791 - GeneralUtility::minifyJavaScript()
========================================================

See :issue:`94791`

Description
===========

The static method :php:`TYPO3\CMS\Core\Utility\GeneralUtility::minifyJavaScript()`
has been marked as deprecated.

Back in TYPO3 4.x times, the "jsmin" library was used to minify
JavaScript, however as this became more flexible, a hook was
introduced, and then "jsmin" was removed again. Since then,
the hook to minify inline JavaScript is used in PageRenderer,
and should rather be moved into the :php:`ResourceCompressor` functionality,
where it resides now.

The hook itself works exactly as before.


Impact
======

Calling the method will trigger a PHP :php:`E_USER_DEPRECATED` error. Extension
scanner will detect calls as strong match.


Affected Installations
======================

TYPO3 installations with custom extensions calling this method,
which is highly unlikely.

Custom extensions using this hook will still work as before without any changes.


Migration
=========

As this method was used to only trigger a hook, it is recommended
to use the :php:`PageRenderer` and :php:`ResourceCompressor` API instead, removing
any direct calls to this method.

If still needed, extension authors can also copy the hook call
execution to use the hook logic, which is not recommended though.

.. index:: PHP-API, FullyScanned, ext:core
