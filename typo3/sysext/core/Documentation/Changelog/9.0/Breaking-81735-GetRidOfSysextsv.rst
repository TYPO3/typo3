.. include:: /Includes.rst.txt

.. _changelog-Breaking-81735-GetRidOfSysextsv:

=======================================
Breaking: #81735 - Get rid of sysext:sv
=======================================

See :issue:`81735`

Description
===========

The AuthenticationService has been moved into system extensios `core`. Class aliases are in place and
will be removed with TYPO3 v10.


Impact
======

The namespace of the classes :php:`AbstractAuthenticationService` and :php:`AuthenticationService` changed
from :php:`\TYPO3\CMS\Sv` to :php:`\TYPO3\CMS\Core\Authentication`.


Affected Installations
======================

All instances, that use or extend the mentioned classes.


Migration
=========

Use the new namespaces as mentioned above.

Related
=======

- :ref:`changelog-MoveOfServicesListReportFromSvToReports`

More Information
================

- :ref:`t3coreapi:services-developer-service-api` in "TYPO3 Explained"

.. index:: Backend, PHP-API, FullyScanned, ext:sv
