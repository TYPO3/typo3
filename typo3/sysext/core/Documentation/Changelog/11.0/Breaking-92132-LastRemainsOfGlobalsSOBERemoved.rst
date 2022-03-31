.. include:: /Includes.rst.txt

=======================================================
Breaking: #92132 - Last remains of globals SOBE removed
=======================================================

See :issue:`92132`

Description
===========

The :php:`$GLOBALS['SOBE']` object has been used as a controller to
sub module communication. It's usage has been reduced in previous core versions
already. It is now fully removed.


Impact
======

Backend extensions that rely on :php:`$GLOBALS['SOBE']` may behave differently.


Affected Installations
======================

Some old backend extensions may still rely on :php:`$GLOBALS['SOBE']` being set.
The extension scanner will find usages.


Migration
=========

Do not rely on :php:`$GLOBALS['SOBE']` being set anymore, hand over arguments to other classes directly.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
