.. include:: /Includes.rst.txt

==============================================
Deprecation: #85978 - GraphicalFunctions->init
==============================================

See :issue:`85978`

Description
===========

The init method of :php:`GraphicalFunctions/Gifbuilder` was used as a constructor to set up various internal properties, and is now transfered into a real constructor, making the extra call to :php:`init()` obsolete.


Impact
======

Calling the method directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with extensions directly calling this method.


Migration
=========

No migration is needed, the constructor of :php:`GraphicalFunctions/Gifbuilder` takes care of the initalization of all settings.

.. index:: PHP-API, NotScanned, ext:core
