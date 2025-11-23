..  include:: /Includes.rst.txt

..  _breaking-106118-1738942498:

==================================================================
Breaking: #106118 - Property DataHandler->storeLogMessages removed
==================================================================

See :issue:`106118`

Description
===========

The public property
:php:`\TYPO3\CMS\Core\DataHandling\DataHandler->storeLogMessages` has
been removed without substitution. It should no longer be used by extensions.

Impact
======

Setting or reading the property in an extension will now raise a PHP warning-
level error.

Affected installations
======================

Instances with extensions that access this property are affected. This should
be a very rare use case. No TYPO3 Extension Repository (TER) extensions were
affected during verification. The extension scanner is configured to find
usages as a weak match.

Migration
=========

The property has been removed. Any code setting or reading it from
:php:`\TYPO3\CMS\Core\DataHandling\DataHandler` instances should be removed. The
:php:`DataHandler->log()` method now always writes the given :php:`$details`
to the :sql:`sys_log` table.

..  index:: PHP-API, FullyScanned, ext:core
