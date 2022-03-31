.. include:: /Includes.rst.txt

=================================
Deprecation: #89742 - Form mixins
=================================

See :issue:`89742`

Description
===========

All mixins in the "form" extension have been deprecated and should not be used anymore. This affects all inheritances from :yaml:`TYPO3.CMS.Form.mixins.*`.

The mixins have been deprecated with TYPO3v10 and will be removed with TYPO3v11.


Impact
======

Form setup inheriting mixins from :yaml:`TYPO3.CMS.Form.mixins.*` will trigger a deprecation warning in TYPO3v10.

With TYPO3v11 these mixins will be removed which will lead to an error.


Affected Installations
======================

Instances using the "form" extension and inheriting from :yaml:`TYPO3.CMS.Form.mixins.*` in their form setup.


Migration
=========

Embed the essential parts from :yaml:`TYPO3.CMS.Form.mixins.*` or migrate them to custom mixins.

.. index:: Backend, Frontend, NotScanned, ext:form
