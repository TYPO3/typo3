
.. include:: /Includes.rst.txt

=====================================================
Breaking: #77587 - Removed LiveSearch->getQueryString
=====================================================

See :issue:`77587`

Description
===========

The public utility method `getQueryString()` within the `LiveSearch` PHP class has been removed.


Impact
======

Calling the method directly will result in a PHP fatal error.


Affected Installations
======================

Any installation extending TYPO3's internal LiveSearch functionality via an extension.


Migration
=========

Use one of the various quoting options shipped with the Doctrine DBAL.

.. index:: PHP-API, Database
