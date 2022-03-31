
.. include:: /Includes.rst.txt

============================================================
Breaking: #73514 - TypoScript property "includeLibs" removed
============================================================

See :issue:`73514`

Description
===========

The TypoScript property to load additional PHP libraries via `.includeLibs` has been removed from the Content
Objects `COA/COA_INT` and `USER/USER_INT`.


Impact
======

Setting the `.includeLibs` property will have no effect anymore.


Affected Installations
======================

Any installation using a very old pi_based extension that does not ship proper class naming or autoloading
information.


Migration
=========

Make sure everything that was previously loaded via includeLibs is now encapsulated in proper PHP classes,
which is referenced by USER/USER_INT when needed.

.. index:: PHP-API, TypoScript
