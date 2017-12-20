.. include:: ../../Includes.txt

=============================================================================
Deprecation: #77524 - Deprecated method fileResource of ContentObjectRenderer
=============================================================================

See :issue:`77524`

Description
===========

The method :php:`ContentObjectRenderer::fileResource()` has been marked as deprecated.


Impact
======

Using the mentioned method will trigger a deprecation log entry.


Affected Installations
======================

Instances that use the method.


Migration
=========

Migrate your code to use :php:`file_get_contents`. Use a call to :php:`$GLOBALS['TSFE']->tmpl->getFileName($fileName)`
for substituting strings like `EXT`.

.. index:: Frontend, PHP-API
