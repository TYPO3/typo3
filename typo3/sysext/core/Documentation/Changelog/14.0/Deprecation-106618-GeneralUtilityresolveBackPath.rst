..  include:: /Includes.rst.txt

..  _deprecation-106618-1745587818:

======================================================
Deprecation: #106618 - GeneralUtility::resolveBackPath
======================================================

See :issue:`106618`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath` has
been marked as deprecated and will be removed in TYPO3 v15.0.

It served as a mechanism to remove relative path segments such as ".." when
referencing files or directories. This was particularly important before TYPO3
v7, when every TYPO3 backend module had its own route and entry point PHP file.
Nowadays, it is a relic from the past.

Since TYPO3 v13, this method has become even less relevant, as both the TYPO3
backend and frontend now share the same main entry point file
(:file:`index.php`).

Impact
======

TYPO3 no longer resolves the back path of resource references or normalizes
paths when rendering or referencing resources in the HTML output - neither in
the frontend nor in the backend.

However, existing references will continue to work.

Affected installations
======================

TYPO3 installations with custom TypoScript inclusions or backend modules that
reference files using relative paths may be affected.
Such usage is uncommon in modern TYPO3 installations.

Migration
=========

References to resources should now use the `EXT:` prefix or be written
relative to the public web path of the TYPO3 installation.

References to JavaScript modules (ES6 modules) should be managed through import
maps using module names instead of relative paths.

..  index:: Backend, Frontend, JavaScript, TypoScript, FullyScanned, ext:core
