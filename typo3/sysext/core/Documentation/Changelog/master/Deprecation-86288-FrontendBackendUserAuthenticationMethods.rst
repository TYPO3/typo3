.. include:: ../../Includes.txt

===============================================================
Deprecation: #86288 - FrontendBackendUserAuthentication methods
===============================================================

See :issue:`86288`

Description
===========

Due to refactorings within AdminPanel, EXT:feedit and via PSR-15 middlewares, the extension class
:php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication`, which is instantiated in Frontend
Requests as :php:`$GLOBALS['BE_USER']` has some unused methods which are now deprecated:

* :php:`checkBackendAccessSettingsFromInitPhp()`
* :php:`extPageReadAccess()`
* :php:`extGetTreeList()`
* :php:`extGetLL()`


Impact
======

Calling any of the methods above will trigger a deprecation warning.


Affected Installations
======================

Any TYPO3 installation with custom PHP code accessing at least one of the methods above.


Migration
=========

Use either methods from :php:`BackendUserAuthentication` directly, or - if in context of Admin Panel or
Frontend Editing - use the API methods within these modules directly, if necessary.

.. index:: FullyScanned