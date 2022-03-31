
.. include:: /Includes.rst.txt

=====================================================
Breaking: #73763 - Removed backPath from PageRenderer
=====================================================

See :issue:`73763`

Description
===========

The PageRenderer class responsible for rendering Frontend output and Backend modules has no option to resolve
the so-called backPath anymore. The second parameter has been dropped from the constructor method. Additionally
the public property `backPath` as well as the method `PageRenderer->setBackPath()` have been removed.


Impact
======

Calling the constructor of PageRenderer with a second parameter, or setting PageRenderer->backPath has no
effect anymore. Calling `PageRenderer->setBackPath()` directly will result in a PHP error.


Affected Installations
======================

Custom installations using the PageRenderer API directly in an extension.


Migration
=========

Simply remove the call to `PageRenderer->setBackPath()` in your own scripts.

.. index:: PHP-API, Backend
