
.. include:: /Includes.rst.txt

=============================================================
Breaking: #76879 - Remove unused properties from PageTreeView
=============================================================

See :issue:`76879`

Description
===========

The following unused properties have been removed from the :php:`PageTreeView` class:

* :php:`ext_separateNotinmenuPages`
* :php:`ext_alphasortNotinmenuPages`


Impact
======

Extensions which use one of the public properties above will throw a fatal error.


Affected Installations
======================

All installations with a 3rd party extension using one of the classes above.


Migration
=========

No migration available.

.. index:: PHP-API, Backend
