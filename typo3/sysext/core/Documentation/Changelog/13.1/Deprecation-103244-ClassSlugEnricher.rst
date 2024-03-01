.. include:: /Includes.rst.txt

.. _deprecation-103244-1709376790:

=========================================
Deprecation: #103244 - Class SlugEnricher
=========================================

See :issue:`103244`

Description
===========

Class :php:`\TYPO3\CMS\Core\DataHandling\SlugEnricher` has been marked as
deprecated in TYPO3 v13 and will be removed with v14.

The class was used as a helper for :php:`\TYPO3\CMS\Core\DataHandling\DataHandler`,
which now inlines the code in a simplified variant.


Impact
======

Using the class will raise a deprecation level log entry and a fatal error in TYPO3 v14.


Affected installations
======================

There is little to no reason to use this class in custom extensions, very few
instances should be affected by this. The extension scanner will find usages
with a strong match.


Migration
=========

No migration available.


.. index:: PHP-API, FullyScanned, ext:core
