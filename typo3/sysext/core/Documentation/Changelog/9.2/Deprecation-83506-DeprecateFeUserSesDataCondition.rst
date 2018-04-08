
.. include:: ../../Includes.txt

===============================================================================
Deprecation: #83506 - Deprecated usage of TSFE:fe_user|sesData in TS conditions
===============================================================================

See :issue:`83506`

Description
===========

Since the session API has been adjusted it is no longer possible to access the (now protected) `sesData` property of
the `fe_user` object.


Impact
======

Using :typoscript:`[globalVar = TSFE:fe_user|sesData|foo|bar = 1234567]` will trigger a deprecation warning.


Affected Installations
======================

Any installation using the old value :typoscript:`TSFE:fe_user|sesData` in a TypoScript condition.


Migration
=========

Use :typoscript:`[globalVar = session:foo|bar = 1234567]` instead.

.. index:: Frontend, TypoScript, NotScanned
