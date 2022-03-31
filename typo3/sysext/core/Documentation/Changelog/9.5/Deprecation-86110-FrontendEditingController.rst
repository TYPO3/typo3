.. include:: /Includes.rst.txt

===============================================
Deprecation: #86110 - FrontendEditingController
===============================================

See :issue:`86110`

Description
===========

The class :php:`\TYPO3\CMS\Core\FrontendEditing\FrontendEditingController` is not in use anymore, only feedit
instantiates the class for legacy reasons.

Also, property :php:`FrontendBackendUserAuthentication->frontendEdit` which holds an instance of
it, has been marked as deprecated.


Impact
======

The functionality of this class has been moved into ext:feedit.
If an instance needs access to frontend editing, it can be accessed from there.


Affected Installations
======================

Instances accessing the deprecated class or function will **NOT** trigger a PHP :php:`E_USER_DEPRECATED` error.


Migration
=========

Refer to ext:feedit for inspiration.

.. index:: PHP-API, FullyScanned, ext:feedit
