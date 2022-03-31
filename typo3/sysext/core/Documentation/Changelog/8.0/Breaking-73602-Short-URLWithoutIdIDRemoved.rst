
.. include:: /Includes.rst.txt

===================================================
Breaking: #73602 - Short-URL without ?id=ID removed
===================================================

See :issue:`73602`

Description
===========

The support for resolving URLs using `index.php?23` instead of `index.php?id=23` with no real GET parameter given
has been removed.

The method `$TSFE->setIDfromArgV()` has been removed as well.


Impact
======

Calling a frontend page with the short-handed URL will result in not detecting a page ID at all.

Calling `$TSFE->setIDfromArgV()` directly within PHP will result in a fatal PHP error.


Affected Installations
======================

Any TYPO3 installation with an extension using the pre-4.0 syntax.


Migration
=========

Use the proper `index.php?id=23` when using URLs to be called in the frontend.

.. index:: PHP-API, Frontend
