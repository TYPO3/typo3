.. include:: ../../Includes.txt

=============================================
Breaking: #85025 - Enumerations are now final
=============================================

See :issue:`85025`

Description
===========

All enumeration classes in TYPO3 have been marked as :php:`final` which prevents extension by 3rd party code.

By definition an enumeration is a limited and known set of values, any code which uses enumeration relies on this fact.
If an enumeration was extended by 3rd party code undefined behavior would occur. For this reason no enumerations must be extended.

Developers of 3rd party extensions are also encouraged to mark their enumerations as :php:`final`.


Impact
======

Classes extending TYPO3 enumerations will trigger a fatal PHP error.


Affected Installations
======================

Instances with classes extending TYPO3 enumerations.


Migration
=========

Remove the classes which extend TYPO3 enumerations.

.. index:: PHP-API, NotScanned