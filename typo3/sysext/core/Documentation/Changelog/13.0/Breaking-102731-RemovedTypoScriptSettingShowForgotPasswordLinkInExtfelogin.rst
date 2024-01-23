.. include:: /Includes.rst.txt

.. _breaking-102731-1703944154:

====================================================================================
Breaking: #102731 - Removed TypoScript setting showForgotPasswordLink in ext:felogin
====================================================================================

See :issue:`102731`

Description
===========

The :php:`showForgotPasswordLink` setting in ext:felogin has never been used in
default Fluid templates and was only been kept for backward compatibility
reasons. The setting has been deprecated with :issue:`98122`, but it has been forgotten
to be removed in TYPO3 v12.


Impact
======

The :php:`showForgotPasswordLink` setting has been removed from default
TypoScript.


Affected installations
======================

Instances using :php:`showForgotPasswordLink` setting in Fluid templates.


Migration
=========

Use :php:`showForgotPassword` instead of :php:`showForgotPasswordLink`, which is
available since TYPO3 v11.

.. index:: Frontend, TypoScript, NotScanned, ext:felogin
