.. include:: ../../Includes.txt

==========================================================================
Deprecation: #85807 - Deprecate EnvironmentService::isEnvironmentInCliMode
==========================================================================

See :issue:`85807`

Description
===========

The method :php:`TYPO3\CMS\Extbase\Service\EnvironmentService::isEnvironmentInCliMode()` has been deprecated.


Impact
======

Calling the method will trigger a deprecation message.


Affected Installations
======================

Any TYPO3 installation with a custom extension calling the method above.


Migration
=========

Use :php:`TYPO3\CMS\Core\Core\Environment::isCli()` as replacement.

.. index:: PHP-API, FullyScanned, ext:extbase