.. include:: ../../Includes.txt

=====================================================================================
Deprecation: #84984 - Protected user TSconfig properties in BackendUserAuthentication
=====================================================================================

See :issue:`84984`

Description
===========

The following properties of class :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication` have been set to protected:

* :php:`->userTS`: use method :php:`->getTSConfig()` instead
* :php:`->userTSUpdated`: Class internal property
* :php:`->userTS_text`: Class internal property
* :php:`->TSdataArray`: Class internal property
* :php:`->userTS_dontGetCached`: Will be removed in v10 without substitution

From the above list, property :php:`->userTS` is the most likely one to be used by extensions.
As a substitution, the full parsed user TSconfig data array can be retrieved calling method :php:`getTSConfig()`.


Impact
======

The properties are still accessible in v9 from outside of the class but will trigger a PHP :php:`E_USER_DEPRECATED` error if used.


Affected Installations
======================

Instances with extensions that add backend modules which can be configured via user TSconfig may be
affected by this change. The extension scanner should find possible usages in extensions.


Migration
=========

Use :php:`->getTSConfig()` instead of :php:`->userTS`.
Do not use the properties marked as internal above.
Remove usage of :php:`userTS_dontGetCached` and configure the UserTSconfig cache via the caching
framework's configuration instead.

.. index:: Backend, PHP-API, TSConfig, FullyScanned