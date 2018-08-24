.. include:: ../../Includes.txt

=================================================================
Deprecation: #85978 - Deprecate GraphicalFunctions->init() method
=================================================================

See :issue:`85978`

Description
===========

The init method of :php:`GraphicalFunctions/Gifbuilder` was used instead of a constructor to set up various internal properties, and is now shifted into a new constructor, making the extra call to init() superfluous.


Impact
======

Calling the method directly will trigger a deprecation warning.


Affected Installations
======================

Any TYPO3 installation with extensions directly calling this method.


Migration
=========

No migration needed, the constructor of :php:`GraphicalFunctions/Gifbuilder` takes care of the initalization of all settings.

.. index:: PHP-API, FullyScanned, ext:core