
.. include:: /Includes.rst.txt

====================================================
Breaking: #64070 - Removed global variable WEBMOUNTS
====================================================

See :issue:`64070`

Description
===========

The global variable WEBMOUNTS has been removed, as the same data from the WEBMOUNTS can always be fetched via
`$GLOBALS['BE_USER']->returnWebmounts()`.

Impact
======

The variable `$GLOBALS['WEBMOUNTS']` will no longer be filled.


Affected installations
======================

Any installation using `$GLOBALS['WEBMOUNTS']` directly within an extension will produce a wrong result.

Migration
=========

Replace all occurrences of `$GLOBALS['WEBMOUNTS']` with `$GLOBALS['BE_USER']->returnWebmounts()`.


.. index:: PHP-API, Backend
