.. include:: ../../Includes.txt

=====================================================
Deprecation: #83503 - Deprecate unneeded RawValidator
=====================================================

See :issue:`83503`


Description
===========

The `RawValidator` does not actually validate anything at all. It was meant to be some kind of NullObject to prevent a
`NoSuchValidatorException` when resolving a validator from the detected type of a param. As these Exceptions are caught,
the Validator has been marked as deprecated and will be removed in CMS 10.


Impact
======

If you rely on the `RawValidator` you will need to implement it yourself.


Affected Installations
======================

All installations that use the `RawValidator`. As the validator does not validate anything the chances are high that it
does not affect anyone at all.


Migration
=========

If needed, create the Validator yourself.

.. index:: PHP-API, PartiallyScanned
