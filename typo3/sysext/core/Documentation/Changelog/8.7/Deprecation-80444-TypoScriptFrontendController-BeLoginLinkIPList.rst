.. include:: ../../Includes.txt

======================================================================
Deprecation: #80444 - TypoScriptFrontendController-> beLoginLinkIPList
======================================================================

See :issue:`80444`

Description
===========

The method :php:`TypoScriptFrontendController->beLoginLinkIPList` has been marked as deprecated.


Impact
======

Calling the PHP method directly will trigger a deprecation warning.


Affected Installations
======================

Any installation instantiating a custom frontend-related RequestHandler or using the method above
when rendering the frontend. Also, any custom extension using this method.


Migration
=========

The functionality is moved to EXT:compatibility7.

.. index:: Frontend, PHP-API
