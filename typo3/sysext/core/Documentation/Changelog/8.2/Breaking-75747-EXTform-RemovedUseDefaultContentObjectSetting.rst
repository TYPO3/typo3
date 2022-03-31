
.. include:: /Includes.rst.txt

=====================================================================
Breaking: #75747 - EXT:form - Removed useDefaultContentObject setting
=====================================================================

See :issue:`75747`

Description
===========

The TypoScript option :typoscript:`useDefaultContentObject` of the FORM cObject has been removed.
Setting this value to 0 allowed the usage of the prehistoric content type `mailform`.


Impact
======

It is not possible to configure the rendering of the FORM cOject. The setting is not evaluated anymore.


Affected Installations
======================

Any installation that uses the TypoScript option :typoscript:`useDefaultContentObject = 0`.


Migration
=========

Remove the TypoScript option from any TypoScript settings. Migrate manually to use the features of EXT:form.

.. index:: TypoScript, ext:form
