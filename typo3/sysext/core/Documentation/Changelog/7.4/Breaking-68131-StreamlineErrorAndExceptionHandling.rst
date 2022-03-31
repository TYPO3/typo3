
.. include:: /Includes.rst.txt

==========================================================
Breaking: #68131 - Streamline error and exception handling
==========================================================

See :issue:`68131`

Description
===========

It is not possible any more to change error and exception handling configuration in an ext_localconf.php of an extension.


Impact
======

Error or exception handling configuration overridden in ext_localonf.php files will not work any more.


Affected Installations
======================

All installations with extension that set error or exception handling configuration in ext_localconf.php files.


Migration
=========

Configure error and exception handling in LocalConfiguration.php or AdditionalConfiguration.php



.. index:: PHP-API, LocalConfiguration
