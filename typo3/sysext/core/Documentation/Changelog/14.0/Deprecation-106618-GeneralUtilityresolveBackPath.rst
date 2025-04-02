..  include:: /Includes.rst.txt

..  _deprecation-106618-1745587818:

======================================================
Deprecation: #106618 - GeneralUtility::resolveBackPath
======================================================

See :issue:`106618`

Description
===========

The method :php:`GeneralUtility::resolveBackPath` has been marked as deprecated
and will be removed in TYPO3 v15.0.

It served as a possibility to remove any relative path segments such as ".."
when referencing files or directories, and was especially important before
TYPO3 v7, where every TYPO3 Backend had their own route and entrypoint PHP file,
but nowadays has been a relict from the past.

Since TYPO3 v13, this method has been even more unrelated as the main
entrypoint file for TYPO3 Backend is now the same as the frontend
("htdocs/index.php").

Impact
======

TYPO3 does not resolve the back path of a reference to a resource and does not
"normalize" the path anymore when rendering or referencing the resource
in the HTML output - neither in the frontend or backend. It will however
continue to work.


Affected installations
======================

TYPO3 installations with custom inclusions in TypoScript, or backend modules
referencing files with relative paths, which is very uncommon in the current web
era.


Migration
=========

Referencing resources should now be done with the `EXT` prefix, or relative
to the public web path of the TYPO3 installation.

Referencing JavaScript modules (ES6 modules) should be handled via import maps
and the module names.

..  index:: Backend, Frontend, JavaScript, TypoScript, FullyScanned, ext:core
