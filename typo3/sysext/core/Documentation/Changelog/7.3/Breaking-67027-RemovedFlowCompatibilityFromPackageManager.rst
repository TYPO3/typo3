
.. include:: ../../Includes.txt

=================================================================
Breaking: #67027 - Removed FLOW-compatibility from PackageManager
=================================================================

See :issue:`67027`

Description
===========

The Package Manager has been simplified and trimmed down to fit the needs of the TYPO3 extensions and typical
Composer packages. All shipped code backported from Flow was removed or refactored to be included in the TYPO3
Core natively. Loading classes are done with the Composer class loader or by the conventions of extension namings.
All default Composer packages can still be included as usual, however the custom Flow-logic has been removed.


Impact
======

It is not possible to add custom Package.php loaders into TYPO3 extensions anymore to be called during runtime. It is
not possible to configure extensions with custom `Classes/` directories and custom composer.json locations anymore.
There is no special handling for "typo3-flow" packages anymore. The :file:`typo3conf/PackageStates.php` file now only
contains the parts that are necessary for the TYPO3 system.


Affected Installations
======================

All installations using custom functionality of the PackageManager not in use with the TYPO3 Core, or installations
trying to use Flow packages natively in the TYPO3 Core.


Migration
=========

Use Composer packages natively for class loading, or use ext_localconf.php to additionally configure a package.


.. index:: PHP-API
