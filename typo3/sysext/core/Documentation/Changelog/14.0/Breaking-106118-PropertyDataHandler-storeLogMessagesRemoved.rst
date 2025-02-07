..  include:: /Includes.rst.txt

..  _breaking-106118-1738942498:

==================================================================
Breaking: #106118 - Property DataHandler->storeLogMessages removed
==================================================================

See :issue:`106118`

Description
===========

Public property :php:`TYPO3\CMS\Core\DataHandling\DataHandler->storeLogMessages`
has been removed without substitution. It should not be used by extensions anymore.


Impact
======

Setting or reading the property in extension raises a PHP warning level error.


Affected installations
======================

Instances with extensions dealing with the property. This should be a very rare
use case, no TER extension was affected when looking this up. The extension scanner
is configured to find usages as weak match.


Migration
=========

The property has been removed, setting or reading it from :php:`DataHandler` instances
should be removed. The :php:`DataHandler->log()` method now always writes given
:php:`$details` to table :sql:`sys_log`.


..  index:: PHP-API, FullyScanned, ext:core
