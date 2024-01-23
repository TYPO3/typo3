.. include:: /Includes.rst.txt

.. _breaking-102895-1706002517:

=============================================
Breaking: #102895 - PackageInterface modified
=============================================

See :issue:`102895`

Description
===========

The PHP interface :php:`\TYPO3\CMS\Core\Package\PackageInterface` has been
modified.

All methods of the interface now have proper types in the method signature.

In addition, the method :php:`getPackageIcon(): ?string` is added to define
whether the package has an icon which is shipped with the package.


Impact
======

Although the interface exists primarily because of the original implementation
from Flow Framework in TYPO3 v6.0 in order to differentiate between TYPO3
extensions and Flow packages, the interface only has one implementation:
:php:`\TYPO3\CMS\Core\Package\Package`.

Thus, it does not impact any extension or installation directly.

However, projects might be affected if there is a custom implementation
of the :php:`PackageInterface`, which is highly unlikely.


Affected installations
======================

TYPO3 installations in very rare cases where there is a custom implementation
of the interface, which is unknown at the time of writing.


Migration
=========

Extend the custom implementation to reflect the updated :php:`PackageInterface`.

.. index:: PHP-API, NotScanned, ext:core
