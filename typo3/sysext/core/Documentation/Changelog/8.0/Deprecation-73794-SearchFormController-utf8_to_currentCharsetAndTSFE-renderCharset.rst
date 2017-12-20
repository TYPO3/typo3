
.. include:: ../../Includes.txt

==========================================================================================
Deprecation: #73794 - SearchFormController->utf8_to_currentCharset and TSFE->renderCharset
==========================================================================================

See :issue:`73794`

Description
===========

The public method `SearchFormController->utf8_to_currentCharset()` within indexed search has been marked as
deprecated.

The public property `TypoScriptFrontendController->renderCharset` has been marked as deprecated.


Impact
======

Calling the method above will result in a deprecation log entry.


Affected Installations
======================

Any installation that extends Indexed Search and uses this method, and any extension that accesses `TSFE->renderCharset` via TypoScript or PHP directly.


Migration
=========

Remove any calls to the method and the public property.

.. index:: PHP-API, Frontend
