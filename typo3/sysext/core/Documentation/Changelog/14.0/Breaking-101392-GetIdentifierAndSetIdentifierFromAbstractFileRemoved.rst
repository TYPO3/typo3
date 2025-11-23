..  include:: /Includes.rst.txt

..  _breaking-101392-1742741515:

=================================================================================
Breaking: #101392 - getIdentifier() and setIdentifier() from AbstractFile removed
=================================================================================

See :issue:`101392`

Description
===========

When using the PHP API of the **File Abstraction Layer (FAL)**, several classes
are involved in representing file objects.

In addition to the
:php-short:`\TYPO3\CMS\Core\Resource\FileInterface`, there is also the
:php-short:`\TYPO3\CMS\Core\Resource\AbstractFile` class, from which most file-
related classes inherit.

To ensure stricter type consistency, the abstract class no longer implements
the methods :php:`getIdentifier()` and :php:`setIdentifier()`. Implementing
these methods is now the responsibility of each subclass.

The methods are now implemented in the respective concrete classes inheriting
from :php-short:`\TYPO3\CMS\Core\Resource\AbstractFile`.

Impact
======

In the unlikely case that the TYPO3 File Abstraction Layer has been extended
with custom PHP classes derived from
:php-short:`\TYPO3\CMS\Core\Resource\AbstractFile`, this change will
cause a fatal PHP error, as the new abstract methods :php:`getIdentifier()` and
:php:`setIdentifier()` must be implemented by the subclass.

Affected installations
======================

TYPO3 installations that include custom code extending the File Abstraction
Layer are affected. Such cases are considered highly uncommon.

Migration
=========

Implement the two methods :php:`getIdentifier()` and :php:`setIdentifier()` in
any custom file class extending
:php-short:`\TYPO3\CMS\Core\Resource\AbstractFile`.

This can also be implemented in older TYPO3 versions to ensure forward
compatibility with TYPO3 v14 and later.

..  index:: PHP-API, NotScanned, ext:core
