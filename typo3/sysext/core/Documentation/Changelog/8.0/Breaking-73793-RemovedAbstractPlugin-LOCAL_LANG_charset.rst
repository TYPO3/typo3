
.. include:: ../../Includes.txt

=============================================================
Breaking: #73793 - Removed AbstractPlugin->LOCAL_LANG_charset
=============================================================

See :issue:`73793`

Description
===========

The public property `$LOCAL_LANG_charset` within AbstractPlugin (formerly known as pi_base) has been removed.

The property was used to hold information which labels, coming from TypoScript that override Language files, need
to be converted to UTF-8.


Impact
======

Accessing the property within own extensions has no effect anymore.


Affected Installations
======================

Installations with extensions that provide plugins derived from `AbstractPlugin` and use this property.


Migration
=========

No migration needed. Make sure that all external TypoScript configuration files are stored with UTF-8 character set.

.. index:: PHP-API, Frontend
