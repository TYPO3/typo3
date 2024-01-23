.. include:: /Includes.rst.txt

.. _breaking-101309-1689061837:

==================================================================
Breaking: #101309 - Introduce type declarations in DriverInterface
==================================================================

See :issue:`101309`

Description
===========

Return and param type declarations have been introduced for all methods stubs
of :php:`\TYPO3\CMS\Core\Resource\Driver\DriverInterface`.

Also, method :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::sanitizeFileName()`
has been removed.


Impact
======

In consequence, all implementations of :php:`\TYPO3\CMS\Core\Resource\Driver\DriverInterface` need
to reflect those changes and add the same return and param type declarations.

In case, any of the Core implementations are extended, overridden methods might need to be adjusted.
The Core classes, implementing :php:`\TYPO3\CMS\Core\Resource\DriverInterface`, are:

- :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractDriver`
- :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver`
- :php:`\TYPO3\CMS\Core\Resource\Driver\LocalDriver`

Concerning removed method :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::sanitizeFileName()`:

Said method didn't sanitize at all, it didn't respect the given :php:`$charset` param and simply
returned the input string. Abstract classes MAY fulfill the interface contract but if they do so,
they MUST do it right. There is no benefit in fulfilling it just signature wise, it MUST fulfill
it functional wise and in this case it didn't. That's why :php:`LocalDriver`
reimplements :php:`sanitizeFileName()` completely.

As a consequence of this removal, all classes that extend either
:php:`\TYPO3\CMS\Core\Resource\Driver\AbstractDriver` or
:php:`\TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver`, need to
implement method :php:`sanitizeFileName()`.


Affected installations
======================

All installations that implement :php:`\TYPO3\CMS\Core\Resource\DriverInterface` or that
extend either :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractDriver` or
:php:`\TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver`.


Migration
=========

As for the type declarations:
Add the same param and return type declarations the interface does.

As for the removed method :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::sanitizeFileName()`:

Implement the method according to your driver capabilities.

.. index:: FAL, PHP-API, NotScanned, ext:core
