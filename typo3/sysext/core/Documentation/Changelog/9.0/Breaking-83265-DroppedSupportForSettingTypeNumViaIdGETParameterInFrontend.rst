.. include:: /Includes.rst.txt

=========================================================================================
Breaking: #83265 - Dropped support for setting "typeNum" via id GET Parameter in Frontend
=========================================================================================

See :issue:`83265`

Description
===========

The functionality to add the possible page :typoscript:`typeNum` to the "id" GET/POST Parameter has been removed.

Previously it was possible to call TYPO3 Frontend via `index.php?id=23.13` (separated with a dot)
which resolved in the page ID being "23" and the typeNum set to 13.

This functionality is a leftover from 2003, to shorten the URL and avoid multiple GET parameters.
Instead, today it is common to use `index.php?id=23&type=13` which TYPO3 uses internally everywhere
since TYPO3 v4.0.


Impact
======

Calling Frontend URLs via `index.php?id=23.13` - adding the typeNum with a dot - will result in a PageNotFound exception.


Affected Installations
======================

Installations with multiple "typeNum" TypoScript values, and with very old settings and custom built URLs for the Frontend.


Migration
=========

Use typolink functionality in TypoScript, or Fluid to build your URLs properly in the format
of `index.php?id=pageId&type=typeNum`.

.. index:: Frontend, NotScanned
